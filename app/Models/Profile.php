<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Profile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'send_weekdays_only' => 'boolean',
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

    public function isRateLimited(): bool
    {
        if (!$this->max_emails_per_hour || $this->max_emails_per_hour <= 0) {
            return false;
        }

        $sentLastHour = JobApplication::where('status', JobApplication::STATUS_SENT)
            ->where('sent_at', '>=', now()->subHour())
            ->count();

        return $sentLastHour >= $this->max_emails_per_hour;
    }
}
