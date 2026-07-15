<?php

namespace App\Jobs;

use App\Mail\JobApplicationMail;
use App\Models\JobApplication;
use App\Models\Profile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
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
            return; // deleted, or already sent — don't double-send
        }

        $profile = Profile::current();

        Mail::to($job->recruiter_email)->send(new JobApplicationMail($job, $profile));

        $job->update([
            'status'  => JobApplication::STATUS_SENT,
            'sent_at' => now(),
            'error'   => null,
        ]);
    }

    /**
     * Runs after the final retry fails — mark the row so the UI can show it.
     */
    public function failed(Throwable $e): void
    {
        JobApplication::where('id', $this->jobApplicationId)->update([
            'status' => JobApplication::STATUS_FAILED,
            'error'  => substr($e->getMessage(), 0, 1000),
        ]);
    }
}
