<?php

namespace App\Console\Commands;

use App\Jobs\SyncGmailInbox;
use App\Models\FeatureFlag;
use App\Models\Profile;
use Illuminate\Console\Command;

class SyncGmailReplies extends Command
{
    protected $signature = 'app:sync-gmail-replies';
    protected $description = 'Sync Gmail replies for every account with connected mail credentials';

    public function handle(): int
    {
        if (!FeatureFlag::enabled('feature.gmail_sync')) {
            $this->info('Automatic Gmail sync is disabled by the administrator — skipping.');
            return self::SUCCESS;
        }

        $count = 0;

        // No timestamp throttle here — GmailImapService::sync() holds a per-user
        // lock, so if the previous minute's sync is still running this just
        // no-ops instead of overlapping. That keeps every tick genuinely a
        // minute apart instead of drifting to ~2 minutes.
        Profile::whereNotNull('mail_username')
            ->whereNotNull('mail_password')
            ->chunkById(100, function ($chunk) use (&$count) {
                foreach ($chunk as $profile) {
                    SyncGmailInbox::dispatch($profile->user_id);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} Gmail sync job(s).");

        return self::SUCCESS;
    }
}
