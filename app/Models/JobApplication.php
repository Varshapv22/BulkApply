<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED  = 'queued';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function scopeSendable($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED]);
    }

    /**
     * Fill the {placeholders} in an email subject/body template with this job's data.
     */
    public function renderTemplate(string $template, Profile $profile): string
    {
        return strtr($template, [
            '{job_title}'      => $this->job_title ?: 'the role',
            '{company}'        => $this->company,
            '{recruiter_name}' => $this->recruiter_name ?: 'Hiring Manager',
            '{location}'       => $this->location ?: '',
            '{job_url}'        => $this->job_url ?: '',
            '{your_name}'      => $profile->full_name ?: '',
        ]);
    }
}
