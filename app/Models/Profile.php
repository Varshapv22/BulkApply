<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Profile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'send_weekdays_only' => 'boolean',
        'preferred_sites'    => 'array',
        // Encrypted at rest with APP_KEY — never stored or transmitted in plain text.
        'mail_password'      => 'encrypted',
    ];

    public const JOB_SITES = [
        'indeed'      => 'Indeed',
        'linkedin'    => 'LinkedIn',
        'glassdoor'   => 'Glassdoor',
        'ziprecruiter'=> 'ZipRecruiter',
        'dice'        => 'Dice',
        'monster'     => 'Monster',
        'careerbuilder' => 'CareerBuilder',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Fetch the current user's profile, or the first profile (for backwards
     * compatibility with the single-user setup).
     */
    public static function current(): self
    {
        if (Auth::check()) {
            return static::where('user_id', Auth::id())->first()
                ?? new static(['user_id' => Auth::id()]);
        }

        return static::first() ?? new static();
    }

    public function hasDocuments(): bool
    {
        return filled($this->resume_path) && filled($this->cover_letter_path);
    }

    /** Whether this account has connected its own email sender. */
    public function hasMailCredentials(): bool
    {
        return filled($this->mail_username) && filled($this->mail_password);
    }

    public function isInsideSendingWindow(): bool
    {
        $hour = (int) now()->format('G');

        if ($this->send_weekdays_only && now()->isWeekend()) {
            return false;
        }

        if ($this->send_start_hour !== null && $this->send_end_hour !== null) {
            return $hour >= $this->send_start_hour && $hour < $this->send_end_hour;
        }

        return true;
    }
}
