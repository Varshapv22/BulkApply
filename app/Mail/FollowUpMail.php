<?php

namespace App\Mail;

use App\Models\JobApplication;
use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowUpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $renderedSubject;
    public string $renderedBody;

    public function __construct(
        public JobApplication $job,
        public Profile $profile,
    ) {
        $this->renderedSubject = 'Following up: ' . $job->renderTemplate(
            $profile->email_subject ?: 'Application for {job_title} at {company}',
            $profile
        );

        $followUpBody = "Dear {recruiter_name},\n\n"
            . "I wanted to follow up on my application for the {job_title} position at {company}. "
            . "I remain very interested in this opportunity and would welcome the chance to discuss how I can contribute to your team.\n\n"
            . "Please let me know if you need any additional information.\n\n"
            . "Best regards,\n{your_name}";

        $this->renderedBody = $job->renderTemplate($followUpBody, $profile);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderedSubject,
            replyTo: $this->profile->email ? [$this->profile->email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.job-application');
    }

    public function attachments(): array
    {
        return [];
    }
}
