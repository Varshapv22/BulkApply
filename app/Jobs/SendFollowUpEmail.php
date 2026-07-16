<?php

namespace App\Jobs;

use App\Mail\FollowUpMail;
use App\Models\JobApplication;
use App\Models\Profile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendFollowUpEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $jobApplicationId)
    {
    }

    public function handle(): void
    {
        $job = JobApplication::find($this->jobApplicationId);

        if (!$job || $job->status !== JobApplication::STATUS_SENT) {
            return;
        }

        // Don't follow up if they already replied
        if ($job->pipeline_status !== 'applied') {
            return;
        }

        $profile = Profile::current();

        Mail::to($job->recruiter_email)->send(new FollowUpMail($job, $profile));

        $job->update([
            'followup_count' => $job->followup_count + 1,
            'followup_at'    => null, // clear so we don't send again
        ]);

        // Fire webhook if configured
        if (filled($profile->webhook_url)) {
            try {
                Http::timeout(10)->post($profile->webhook_url, [
                    'event'   => 'followup_sent',
                    'company' => $job->company,
                    'email'   => $job->recruiter_email,
                ]);
            } catch (Throwable) {
                // Don't fail the job for webhook errors
            }
        }
    }
}
