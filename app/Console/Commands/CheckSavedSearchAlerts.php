<?php

namespace App\Console\Commands;

use App\Jobs\CheckSavedSearchAlert;
use App\Models\FeatureFlag;
use App\Models\SavedSearch;
use Illuminate\Console\Command;

class CheckSavedSearchAlerts extends Command
{
    protected $signature = 'app:check-saved-search-alerts';
    protected $description = 'Re-run active saved searches and notify users of new listings since their last check';

    public function handle(): int
    {
        if (!FeatureFlag::enabled('feature.saved_search_alerts')) {
            $this->info('Saved search alerts are disabled by the administrator — skipping.');
            return self::SUCCESS;
        }

        $count = 0;

        SavedSearch::active()
            ->where(function ($q) {
                $q->whereNull('last_checked_at')->orWhere('last_checked_at', '<=', now()->subHours(3));
            })
            ->chunkById(100, function ($chunk) use (&$count) {
                foreach ($chunk as $savedSearch) {
                    CheckSavedSearchAlert::dispatch($savedSearch->id);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} saved search check(s).");

        return self::SUCCESS;
    }
}
