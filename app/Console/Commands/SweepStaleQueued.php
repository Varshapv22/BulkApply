<?php

namespace App\Console\Commands;

use App\Jobs\SendJobApplication;
use App\Models\JobApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SweepStaleQueued extends Command
{
    protected $signature = 'app:sweep-stale-queued';
    protected $description = 'Reset job applications stuck in queued status back to pending (crashed worker, abandoned batch)';

    public function handle(): int
    {
        $stale = JobApplication::where('status', JobApplication::STATUS_QUEUED)
            ->where('updated_at', '<', now()->subMinutes(30))
            ->get();

        $reset = 0;

        foreach ($stale as $job) {
            $profile = SendJobApplication::resolveProfileFor($job);

            if ($profile->current_send_batch_id) {
                $batch = Bus::findBatch($profile->current_send_batch_id);
                if ($batch && ! $batch->finished() && ! $batch->cancelled()) {
                    // Still legitimately waiting out a rate-limit release.
                    continue;
                }
            }

            $job->update(['status' => JobApplication::STATUS_PENDING]);
            $reset++;
        }

        $this->info("Reset {$reset} stale queued application(s) back to pending.");

        return self::SUCCESS;
    }
}
