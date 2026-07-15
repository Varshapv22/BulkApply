<?php

namespace App\Mail;

use App\Models\JobApplication;
use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class JobApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $renderedSubject;
    public string $renderedBody;

    public function __construct(
        public JobApplication $job,
        public Profile $profile,
    ) {
        $this->renderedSubject = $job->renderTemplate(
            $profile->email_subject ?: 'Application for {job_title} at {company}',
            $profile
        );
        $this->renderedBody = $job->renderTemplate($profile->email_body ?: '', $profile);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderedSubject,
            replyTo: $this->profile->email
                ? [$this->profile->email]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job-application',
        );
    }

    /**
     * Attach the resume and cover letter from the local storage disk, keeping
     * their original filenames.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->profile->resume_path && Storage::disk('local')->exists($this->profile->resume_path)) {
            $attachments[] = Attachment::fromStorageDisk('local', $this->profile->resume_path)
                ->as($this->profile->resume_name ?: 'resume.pdf');
        }

        if ($this->profile->cover_letter_path && Storage::disk('local')->exists($this->profile->cover_letter_path)) {
            $attachments[] = Attachment::fromStorageDisk('local', $this->profile->cover_letter_path)
                ->as($this->profile->cover_letter_name ?: 'cover-letter.pdf');
        }

        return $attachments;
    }
}
