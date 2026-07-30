<?php

namespace App\Jobs;

use App\Models\Profile;
use App\Models\User;
use App\Services\GmailImapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncGmailInbox implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $userId)
    {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $result = (new GmailImapService())->sync($user);

        Profile::where('user_id', $this->userId)->update(['gmail_synced_at' => now()]);

        if ($result['error']) {
            Log::warning("Gmail auto-sync failed for user {$this->userId}: {$result['error']}");
        }
    }
}
