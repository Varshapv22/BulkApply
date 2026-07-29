<?php

namespace App\Services;

use App\Models\GmailReply;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\User;

class GmailImapService
{
    /**
     * Connect to the user's Gmail via IMAP and pull in any replies
     * from recruiter email addresses stored in their job applications.
     *
     * @return array{synced: int, error: string|null}
     */
    public function sync(User $user): array
    {
        if (!extension_loaded('imap')) {
            return ['synced' => 0, 'error' => 'The PHP IMAP extension is not enabled on this server.'];
        }

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile || !$profile->hasMailCredentials()) {
            return ['synced' => 0, 'error' => 'Connect your Gmail in Profile → Email Sending first.'];
        }

        // Collect all unique recruiter emails from sent applications
        $recruiterEmails = JobApplication::where('user_id', $user->id)
            ->where('status', JobApplication::STATUS_SENT)
            ->whereNotNull('recruiter_email')
            ->pluck('recruiter_email')
            ->map(fn ($e) => strtolower(trim($e)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($recruiterEmails)) {
            return ['synced' => 0, 'error' => null];
        }

        // Open Gmail IMAP (suppress PHP warnings; check return value instead)
        $mailbox = @imap_open(
            '{imap.gmail.com:993/imap/ssl}INBOX',
            $profile->mail_username,
            $profile->mail_password,   // auto-decrypted by the 'encrypted' cast
            0,
            1
        );

        if (!$mailbox) {
            $err = imap_last_error();
            return ['synced' => 0, 'error' => 'Could not connect to Gmail: ' . ($err ?: 'check your App Password.')];
        }

        try {
            return $this->fetchReplies($mailbox, $user, $recruiterEmails);
        } finally {
            @imap_close($mailbox);
        }
    }

    private function fetchReplies($mailbox, User $user, array $recruiterEmails): array
    {
        // Look at the last 90 days — avoids loading thousands of old messages
        $since = now()->subDays(90)->format('j-M-Y');
        $messageNums = @imap_search($mailbox, 'SINCE "' . $since . '"') ?: [];

        // Build a lookup: recruiter_email -> job_application_id
        $appLookup = JobApplication::where('user_id', $user->id)
            ->where('status', JobApplication::STATUS_SENT)
            ->whereIn('recruiter_email', $recruiterEmails)
            ->orderByDesc('sent_at')
            ->get()
            ->keyBy(fn ($a) => strtolower(trim($a->recruiter_email)));

        // Already-stored message IDs to avoid redundant DB queries per loop
        $stored = GmailReply::where('user_id', $user->id)
            ->whereNotNull('message_id')
            ->pluck('message_id')
            ->flip()          // flip to use isset() instead of in_array()
            ->all();

        $synced = 0;

        foreach ($messageNums as $msgNum) {
            $header = @imap_headerinfo($mailbox, $msgNum);
            if (!$header || empty($header->from)) {
                continue;
            }

            $fromObj   = $header->from[0];
            $fromEmail = strtolower(trim(($fromObj->mailbox ?? '') . '@' . ($fromObj->host ?? '')));

            // Only keep emails from one of our recruiter addresses
            if (!in_array($fromEmail, $recruiterEmails, true)) {
                continue;
            }

            // Deduplication via Message-ID header
            $messageId = isset($header->message_id)
                ? trim($header->message_id)
                : ($fromEmail . '_' . ($header->udate ?? $msgNum));

            if (isset($stored[$messageId])) {
                continue;
            }
            $stored[$messageId] = true;

            $fromName = '';
            if (!empty($fromObj->personal)) {
                $fromName = @imap_utf8($fromObj->personal);
            }

            $subject = isset($header->subject)
                ? @imap_utf8($header->subject)
                : '(no subject)';

            $snippet  = $this->extractSnippet($mailbox, $msgNum);
            $received = isset($header->udate) ? now()->setTimestamp($header->udate) : now();
            $application = $appLookup[$fromEmail] ?? null;

            GmailReply::create([
                'user_id'            => $user->id,
                'job_application_id' => $application?->id,
                'message_id'         => $messageId,
                'from_name'          => $fromName ?: null,
                'from_email'         => $fromEmail,
                'subject'            => $subject,
                'snippet'            => $snippet ?: null,
                'received_at'        => $received,
                'is_read'            => false,
            ]);

            // A recruiter reply means the pipeline has moved past "applied" —
            // advance it automatically. Only from "applied" specifically, so
            // this never regresses a stage the user already set by hand (e.g.
            // Interview/Offer) back down to "replied" on a later reply.
            if ($application && $application->pipeline_status === 'applied') {
                $application->update(['pipeline_status' => 'replied']);
            }

            $synced++;
        }

        return ['synced' => $synced, 'error' => null];
    }

    /**
     * Pull a short plain-text preview from the message body.
     */
    private function extractSnippet($mailbox, int $msgNum): string
    {
        try {
            $structure = @imap_fetchstructure($mailbox, $msgNum);
            if (!$structure) {
                return '';
            }

            // Single-part message
            if (!isset($structure->parts)) {
                $body = @imap_fetchbody($mailbox, $msgNum, '1') ?: '';
                $body = $this->decode($body, $structure->encoding ?? 0);
                return mb_substr(trim(strip_tags($body)), 0, 300);
            }

            // Multi-part: look for text/plain first, then text/html
            foreach ($structure->parts as $i => $part) {
                if ($part->subtype === 'PLAIN') {
                    $body = @imap_fetchbody($mailbox, $msgNum, (string) ($i + 1)) ?: '';
                    $body = $this->decode($body, $part->encoding ?? 0);
                    return mb_substr(trim(strip_tags($body)), 0, 300);
                }
            }

            foreach ($structure->parts as $i => $part) {
                if ($part->subtype === 'HTML') {
                    $body = @imap_fetchbody($mailbox, $msgNum, (string) ($i + 1)) ?: '';
                    $body = $this->decode($body, $part->encoding ?? 0);
                    return mb_substr(trim(strip_tags($body)), 0, 300);
                }
            }
        } catch (\Throwable) {
            // Non-fatal — return empty snippet
        }

        return '';
    }

    private function decode(string $body, int $encoding): string
    {
        return match ($encoding) {
            3 => base64_decode($body),          // BASE64
            4 => quoted_printable_decode($body), // QUOTED-PRINTABLE
            default => $body,
        };
    }
}
