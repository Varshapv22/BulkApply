<?php

namespace App\Jobs;

use App\Mail\FollowUpMail;
use App\Models\AdminNotification;
use App\Models\JobApplication;
use App\Services\SafeUrlGuard;
use App\Services\UserMailer;
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

        // Queue workers have no logged-in session, so Profile::current() can't
        // be trusted here — resolve the account that actually owns this job.
        $profile = SendJobApplication::resolveProfileFor($job);

        if (!$profile->hasMailCredentials()) {
            // Credentials were disconnected after the original send — don't
            // retry forever; clear the schedule and let the user know.
            $job->update(['followup_at' => null]);
            AdminNotification::log('followup_failed', "Follow-up for {$job->company} skipped: email sending not connected.", ['job_id' => $job->id]);
            return;
        }

        // Send through THIS account's own connected Gmail, same as the
        // original application email — never a shared/default mailer.
        $userMailer = new UserMailer();
        $mailerName = $userMailer->mailerFor($profile);
        try {
            Mail::mailer($mailerName)->to($job->recruiter_email)->send(new FollowUpMail($job, $profile));
        } finally {
            $userMailer->release($mailerName);
        }

        $job->update([
            'followup_count' => $job->followup_count + 1,
            'followup_at'    => null, // clear so we don't send again
        ]);

        // Fire webhook if configured
        if (filled($profile->webhook_url) && SafeUrlGuard::isSafe($profile->webhook_url)) {
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

    public function failed(Throwable $e): void
    {
        $job = JobApplication::find($this->jobApplicationId);
        if ($job) {
            AdminNotification::log('followup_failed', "Follow-up email for {$job->company} failed: " . substr($e->getMessage(), 0, 200), ['job_id' => $job->id]);
        }
    }
}
