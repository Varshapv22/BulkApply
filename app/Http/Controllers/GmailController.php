<?php

namespace App\Http\Controllers;

use App\Models\GmailReply;
use App\Models\Profile;
use App\Services\GmailImapService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmailController extends Controller
{
    /** Newest replies loaded per page view — plenty for an inbox, bounded for memory. */
    private const LIMIT = 200;

    /**
     * The Company Replies inbox (sidebar → Replies).
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'unread' => $request->boolean('unread'),
        ];

        $replies = GmailReply::where('user_id', $userId)
            ->with('jobApplication:id,company,job_title,pipeline_status')
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $term = '%' . $filters['search'] . '%';
                $q->where(fn ($w) => $w->where('subject', 'like', $term)
                    ->orWhere('from_email', 'like', $term)
                    ->orWhere('from_name', 'like', $term)
                    ->orWhere('snippet', 'like', $term));
            })
            ->when($filters['unread'], fn ($q) => $q->where('is_read', false))
            ->latest('received_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'from_name'   => $r->from_name,
                'from_email'  => $r->from_email,
                'subject'     => $r->subject,
                'snippet'     => $r->snippet,
                'message_id'  => $r->message_id,
                'received_at' => $r->received_at?->diffForHumans(),
                'received_on' => $r->received_at?->format('M j, Y g:i A'),
                'is_read'     => $r->is_read,
                'company'     => $r->jobApplication?->company,
                'job_title'   => $r->jobApplication?->job_title,
                'job_id'      => $r->job_application_id,
            ]);

        return Inertia::render('Replies', [
            'replies'        => $replies->values(),
            'filters'        => $filters,
            'gmailConnected' => Profile::where('user_id', $userId)->first()?->hasMailCredentials() ?? false,
            'counts'         => [
                'total'   => GmailReply::where('user_id', $userId)->count(),
                'unread'  => GmailReply::where('user_id', $userId)->where('is_read', false)->count(),
                'matched' => GmailReply::where('user_id', $userId)->whereNotNull('job_application_id')->count(),
            ],
        ]);
    }

    /**
     * Trigger an IMAP sync for the authenticated user.
     */
    public function sync(Request $request)
    {
        $result = (new GmailImapService())->sync($request->user());

        if ($result['error']) {
            return back()->with('gmail_error', $result['error']);
        }

        $msg = $result['synced'] > 0
            ? 'Synced ' . $result['synced'] . ' new ' . ($result['synced'] === 1 ? 'reply' : 'replies') . '.'
            : 'No new replies found.';

        return back()->with('gmail_status', $msg);
    }

    /**
     * Mark a reply as read (called from the dashboard card and the inbox).
     */
    public function markRead(Request $request, GmailReply $reply)
    {
        abort_if($reply->user_id !== $request->user()->id, 403);
        $reply->update(['is_read' => true]);
        return response()->noContent();
    }

    /**
     * Clear the unread badge in one go from the inbox toolbar.
     */
    public function markAllRead(Request $request)
    {
        GmailReply::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back();
    }
}
