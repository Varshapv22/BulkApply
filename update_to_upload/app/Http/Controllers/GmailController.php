<?php

namespace App\Http\Controllers;

use App\Models\GmailReply;
use App\Services\GmailImapService;
use Illuminate\Http\Request;

class GmailController extends Controller
{
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
     * Mark a reply as read (called from the dashboard card).
     */
    public function markRead(Request $request, GmailReply $reply)
    {
        abort_if($reply->user_id !== $request->user()->id, 403);
        $reply->update(['is_read' => true]);
        return response()->noContent();
    }
}
