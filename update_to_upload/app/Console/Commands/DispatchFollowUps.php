<?php

namespace App\Console\Commands;

use App\Jobs\SendFollowUpEmail;
use App\Models\FeatureFlag;
use App\Models\JobApplication;
use Illuminate\Console\Command;

class DispatchFollowUps extends Command
{
    protected $signature = 'app:dispatch-followups';
    protected $description = 'Dispatch follow-up emails for applications past their follow-up date';

    public function handle(): int
    {
        if (!FeatureFlag::enabled('feature.followups')) {
            $this->info('Follow-up emails are disabled by the administrator — skipping.');
            return self::SUCCESS;
        }

        $jobs = JobApplication::where('status', JobApplication::STATUS_SENT)
            ->where('pipeline_status', 'applied')
            ->whereNotNull('followup_at')
            ->where('followup_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($jobs as $job) {
            SendFollowUpEmail::dispatch($job->id);
            $count++;
        }

        $this->info("Dispatched {$count} follow-up email(s).");

        return self::SUCCESS;
    }
}
