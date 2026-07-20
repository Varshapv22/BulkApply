<?php

namespace App\Jobs;

use App\Mail\JobApplicationMail;
use App\Models\EmailTemplate;
use App\Models\JobApplication;
use App\Models\Profile;
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

        $profile = Profile::current();

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

        Mail::to($job->recruiter_email)->send(
            new JobApplicationMail($job, $profile, $customSubject, $customBody)
        );

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

            $profile = Profile::current();
            $this->fireWebhook($profile, $job, 'application_failed');
        }
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
