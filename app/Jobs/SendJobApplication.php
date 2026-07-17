<?php

namespace App\Jobs;

use App\Mail\JobApplicationMail;
use App\Models\EmailTemplate;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Services\UserMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendJobApplication implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $jobApplicationId)
    {
    }

    public function handle(): void
    {
        $job = JobApplication::find($this->jobApplicationId);

        if (! $job || $job->status === JobApplication::STATUS_SENT) {
            return;
        }

        // Queue workers have no logged-in session, so Profile::current() can't
        // be trusted here — resolve the account that actually owns this job.
        $profile = $this->resolveProfile($job);

        if (! $profile->hasMailCredentials()) {
            $job->update([
                'status' => JobApplication::STATUS_FAILED,
                'error'  => 'Email sending not connected. Add your Gmail App Password in Settings.',
            ]);
            return;
        }

        // Rate limit check — release back to queue if over limit
        if ($profile->isRateLimited()) {
            $this->release(120); // try again in 2 minutes
            return;
        }

        // Scheduling window check — release if outside window
        if (! $profile->isInsideSendingWindow()) {
            $this->release(300); // try again in 5 minutes
            return;
        }

        // Assign tracking ID if not set
        if (! $job->tracking_id) {
            $job->tracking_id = Str::uuid()->toString();
            $job->save();
        }

        // Resolve email template: job-specific > default template > profile template
        $customSubject = null;
        $customBody = null;

        $template = $job->email_template_id
            ? EmailTemplate::find($job->email_template_id)
            : EmailTemplate::defaultTemplate();

        if ($template) {
            $customSubject = $template->subject;
            $customBody = $template->body;
        }

        // Send through THIS account's own connected Gmail, isolated per-user
        // so credentials never bleed across jobs processed in the same worker.
        $userMailer = new UserMailer();
        $mailerName = $userMailer->mailerFor($profile);
        try {
            Mail::mailer($mailerName)->to($job->recruiter_email)->send(
                new JobApplicationMail($job, $profile, $customSubject, $customBody)
            );
        } finally {
            $userMailer->release($mailerName);
        }

        $updateData = [
            'status'  => JobApplication::STATUS_SENT,
            'sent_at' => now(),
            'error'   => null,
        ];

        // Schedule follow-up if configured
        if ($profile->followup_days > 0) {
            $updateData['followup_at'] = now()->addDays($profile->followup_days);
        }

        $job->update($updateData);

        // Fire webhook notification
        $this->fireWebhook($profile, $job, 'application_sent');
    }

    public function failed(Throwable $e): void
    {
        $job = JobApplication::find($this->jobApplicationId);
        if ($job) {
            $job->update([
                'status' => JobApplication::STATUS_FAILED,
                'error'  => substr($e->getMessage(), 0, 1000),
            ]);

            $profile = $this->resolveProfile($job);
            $this->fireWebhook($profile, $job, 'application_failed');
        }
    }

    /**
     * The account that owns this job. Falls back to Profile::current() only
     * for legacy rows created before applications tracked user_id.
     */
    private function resolveProfile(JobApplication $job): Profile
    {
        if ($job->user_id) {
            return Profile::where('user_id', $job->user_id)->first()
                ?? new Profile(['user_id' => $job->user_id]);
        }

        return Profile::current();
    }

    private function fireWebhook(Profile $profile, JobApplication $job, string $event): void
    {
        if (blank($profile->webhook_url)) {
            return;
        }

        try {
            Http::timeout(10)->post($profile->webhook_url, [
                'event'     => $event,
                'company'   => $job->company,
                'job_title' => $job->job_title,
                'email'     => $job->recruiter_email,
                'status'    => $job->status,
                'sent_at'   => $job->sent_at?->toIso8601String(),
                'error'     => $job->error,
            ]);
        } catch (Throwable) {
            // Don't fail the job for webhook errors
        }
    }
}
