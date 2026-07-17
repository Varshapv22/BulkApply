<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\SkillExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class ProfileController extends Controller
{
    private const DEFAULT_BODY = <<<TXT
Dear {recruiter_name},

I am writing to apply for the {job_title} position at {company}. I have attached my resume and cover letter for your review.

I would welcome the opportunity to discuss how my experience fits this role. Thank you for your time and consideration.

Best regards,
{your_name}
TXT;

    public function edit()
    {
        $profile = Profile::current();

        return Inertia::render('Profile', [
            'profile' => [
                'full_name'           => $profile->full_name,
                'email'               => $profile->email,
                'phone'               => $profile->phone,
                'location'            => $profile->location,
                'preferred_role'      => $profile->preferred_role,
                'preferred_sites'     => $profile->preferred_sites ?? [],
                'skills'              => $profile->skills,
                'email_subject'       => $profile->email_subject,
                'email_body'          => $profile->email_body,
                'resume_name'         => $profile->resume_name,
                'cover_letter_name'   => $profile->cover_letter_name,
                'send_start_hour'     => $profile->send_start_hour,
                'send_end_hour'       => $profile->send_end_hour,
                'send_weekdays_only'  => (bool) $profile->send_weekdays_only,
                'max_emails_per_hour' => $profile->max_emails_per_hour,
                'followup_days'       => $profile->followup_days,
                'webhook_url'         => $profile->webhook_url,
                // The password is write-only — never sent back to the browser,
                // even encrypted. Only its connection status is exposed.
                'mail_username'       => $profile->mail_username,
                'mail_connected'      => $profile->hasMailCredentials(),
                'mail_from_name'      => $profile->mail_from_name,
            ],
            'jobSites'    => Profile::JOB_SITES,
            'defaultBody' => self::DEFAULT_BODY,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'full_name'          => ['nullable', 'string', 'max:255'],
            'email'              => ['nullable', 'email', 'max:255'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'location'           => ['nullable', 'string', 'max:255'],
            'email_subject'      => ['required', 'string', 'max:255'],
            'email_body'         => ['required', 'string'],
            'resume'             => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'cover_letter'       => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'send_start_hour'    => ['nullable', 'integer', 'min:0', 'max:23'],
            'send_end_hour'      => ['nullable', 'integer', 'min:0', 'max:23'],
            'send_weekdays_only' => ['nullable'],
            'max_emails_per_hour'=> ['nullable', 'integer', 'min:0', 'max:1000'],
            'followup_days'      => ['nullable', 'integer', 'min:0', 'max:30'],
            'webhook_url'        => ['nullable', 'url', 'max:2048'],
            'preferred_role'     => ['nullable', 'string', 'max:255'],
            'preferred_sites'    => ['nullable', 'array'],
            'preferred_sites.*'  => ['string'],
            'skills'             => ['nullable', 'string', 'max:2000'],
            'mail_username'      => ['nullable', 'email', 'max:255'],
            'mail_password'      => ['nullable', 'string', 'max:255'],
            'mail_from_name'     => ['nullable', 'string', 'max:255'],
            'mail_disconnect'    => ['nullable', 'boolean'],
        ]);

        $profile = Profile::current();

        $profile->fill([
            'full_name'          => $data['full_name'] ?? null,
            'email'              => $data['email'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'location'           => $data['location'] ?? null,
            'email_subject'      => $data['email_subject'],
            'email_body'         => $data['email_body'],
            'send_start_hour'    => $data['send_start_hour'] ?? null,
            'send_end_hour'      => $data['send_end_hour'] ?? null,
            'send_weekdays_only' => $request->boolean('send_weekdays_only'),
            'max_emails_per_hour'=> $data['max_emails_per_hour'] ?? 0,
            'followup_days'      => $data['followup_days'] ?? 0,
            'webhook_url'        => $data['webhook_url'] ?? null,
            'preferred_role'     => $data['preferred_role'] ?? null,
            'preferred_sites'    => $data['preferred_sites'] ?? [],
            'skills'             => $data['skills'] ?? null,
        ]);

        if ($request->boolean('mail_disconnect')) {
            $profile->mail_username  = null;
            $profile->mail_password  = null;
            $profile->mail_from_name = null;
        } else {
            if (array_key_exists('mail_username', $data)) {
                $profile->mail_username = $data['mail_username'] ?? null;
            }
            // Leave the password field blank on re-save to keep the currently
            // stored one — only overwrite when the user actually typed a new one.
            if (filled($data['mail_password'] ?? null)) {
                $profile->mail_password = $data['mail_password'];
            }
            if (array_key_exists('mail_from_name', $data)) {
                $profile->mail_from_name = $data['mail_from_name'] ?? null;
            }
        }

        if ($request->hasFile('resume')) {
            $this->deleteIfExists($profile->resume_path);
            $file = $request->file('resume');
            $profile->resume_path = $file->store('documents');
            $profile->resume_name = $file->getClientOriginalName();
        }

        if ($request->hasFile('cover_letter')) {
            $this->deleteIfExists($profile->cover_letter_path);
            $file = $request->file('cover_letter');
            $profile->cover_letter_path = $file->store('documents');
            $profile->cover_letter_name = $file->getClientOriginalName();
        }

        $profile->save();

        return redirect()->route('profile.edit')->with('status', 'Profile saved.');
    }

    /**
     * Parse the uploaded resume to extract name and email.
     */
    public function parseResume(Request $request)
    {
        $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $file = $request->file('resume');
        $ext  = strtolower($file->getClientOriginalExtension());
        $text = '';

        if ($ext === 'docx') {
            $text = $this->extractTextFromDocx($file->getRealPath());
        } elseif ($ext === 'pdf') {
            $text = $this->extractTextFromPdf($file->getRealPath());
        } elseif ($ext === 'doc') {
            $text = $this->extractTextFromDoc($file->getRealPath());
        }

        $extracted = [
            'name'   => null,
            'email'  => null,
            'phone'  => null,
            'skills' => null,
        ];

        // Detect skills mentioned in the resume so Find Jobs can highlight matches.
        $skills = (new SkillExtractor())->extract($text);
        if (!empty($skills)) {
            $extracted['skills'] = implode(', ', $skills);
        }

        // Extract email
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            $extracted['email'] = $m[0];
        }

        // Extract phone
        if (preg_match('/(?:\+?\d{1,3}[\s\-]?)?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{4}/', $text, $m)) {
            $extracted['phone'] = $m[0];
        }

        // Extract name (first non-empty line that looks like a name)
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        foreach ($lines as $line) {
            // Skip lines that look like email/phone/url
            if (preg_match('/@|http|www\.|\.com|\.org|\d{5,}/', $line)) continue;
            // A name is typically 2-4 words, letters only
            if (preg_match('/^[A-Z][a-zA-Z\'\-]+(?:\s+[A-Z][a-zA-Z\'\-]+){1,3}$/', $line)) {
                $extracted['name'] = $line;
                break;
            }
        }

        return response()->json($extracted);
    }

    private function extractTextFromDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return '';

        $content = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$content) return '';

        // Strip XML tags to get text
        $content = str_replace('</w:p>', "\n", $content);
        return strip_tags($content);
    }

    /**
     * Real PDF text extraction (handles compressed streams and font/ToUnicode
     * character maps). A naive byte-level regex scan — the previous approach
     * here — produces garbage for most modern PDFs (Word/Google Docs/Canva
     * exports), so name/email/phone/skills silently failed to extract.
     */
    private function extractTextFromPdf(string $path): string
    {
        try {
            return (new PdfParser())->parseFile($path)->getText();
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function extractTextFromDoc(string $path): string
    {
        $content = file_get_contents($path);
        if (!$content) return '';

        // Basic text extraction from .doc binary
        $text = '';
        $length = strlen($content);
        for ($i = 0; $i < $length; $i++) {
            $char = ord($content[$i]);
            if ($char >= 32 && $char <= 126) {
                $text .= chr($char);
            } elseif ($char === 13 || $char === 10) {
                $text .= "\n";
            }
        }

        return $text;
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
