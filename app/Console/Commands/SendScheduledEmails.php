<?php

namespace App\Console\Commands;

use App\Jobs\SendJobApplication;
use App\Models\JobApplication;
use App\Models\Profile;
use Illuminate\Console\Command;

class SendScheduledEmails extends Command
{
    protected $signature = 'app:send-scheduled';
    protected $description = 'Process queued emails within the configured sending window';

    public function handle(): int
    {
        $profile = Profile::current();

        // Check if we're inside the sending window
        if (!$this->insideSendingWindow($profile)) {
            $this->info('Outside sending window, skipping.');
            return self::SUCCESS;
        }

        // Check rate limit
        $maxPerHour = $profile->max_emails_per_hour ?: 0;
        if ($maxPerHour > 0) {
            $sentLastHour = JobApplication::where('status', JobApplication::STATUS_SENT)
                ->where('sent_at', '>=', now()->subHour())
                ->count();

            if ($sentLastHour >= $maxPerHour) {
                $this->info("Rate limit reached ({$sentLastHour}/{$maxPerHour} per hour).");
                return self::SUCCESS;
            }

            // Only dispatch up to the remaining capacity
            $capacity = $maxPerHour - $sentLastHour;
            $jobs = JobApplication::where('status', JobApplication::STATUS_QUEUED)->limit($capacity)->get();
        } else {
            $jobs = JobApplication::where('status', JobApplication::STATUS_QUEUED)->get();
        }

        foreach ($jobs as $job) {
            SendJobApplication::dispatch($job->id);
        }

        $this->info("Dispatched {$jobs->count()} email(s).");

        return self::SUCCESS;
    }

    private function insideSendingWindow(Profile $profile): bool
    {
        $hour = (int) now()->format('G');

        if ($profile->send_weekdays_only && now()->isWeekend()) {
            return false;
        }

        if ($profile->send_start_hour !== null && $profile->send_end_hour !== null) {
            return $hour >= $profile->send_start_hour && $hour < $profile->send_end_hour;
        }

        return true;
    }
}
