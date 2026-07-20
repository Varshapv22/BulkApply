<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED  = 'queued';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    public const PIPELINE_STATUSES = [
        'applied'   => 'Applied',
        'replied'   => 'Replied',
        'interview' => 'Interview',
        'rejected'  => 'Rejected',
        'offer'     => 'Offer',
    ];

    protected $guarded = [];

    protected $casts = [
        'sent_at'    => 'datetime',
        'opened_at'  => 'datetime',
        'clicked_at' => 'datetime',
        'followup_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }

    public function scopeSendable($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED]);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) return $query;
        return $query->where(function ($q) use ($search) {
            $q->where('company', 'like', "%{$search}%")
              ->orWhere('job_title', 'like', "%{$search}%")
              ->orWhere('recruiter_name', 'like', "%{$search}%")
              ->orWhere('recruiter_email', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    public function scopeFilterStatus($query, ?string $status)
    {
        if (!$status) return $query;
        return $query->where('status', $status);
    }

    public function scopeFilterPipeline($query, ?string $pipeline)
    {
        if (!$pipeline) return $query;
        return $query->where('pipeline_status', $pipeline);
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
            '{your_location}'  => $profile->location ?: '',
            '{your_email}'     => $profile->email ?: '',
            '{your_phone}'     => $profile->phone ?: '',
        ]);
    }
}
