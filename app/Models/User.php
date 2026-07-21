<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()->active()->latest('starts_at')->first();
    }

    public function activePlan(): ?Plan
    {
        return $this->activeSubscription()?->plan;
    }

    /** Emails still sendable under the active plan's limit since the subscription started. Null = unlimited. */
    public function remainingEmailQuota(): ?int
    {
        $subscription = $this->activeSubscription();
        $limit = $subscription?->plan?->email_limit;

        if ($limit === null) {
            return null;
        }

        $sentSinceSubscription = JobApplication::where('user_id', $this->id)
            ->where('status', JobApplication::STATUS_SENT)
            ->where('sent_at', '>=', $subscription->starts_at ?? $subscription->created_at)
            ->count();

        return max(0, $limit - $sentSinceSubscription);
    }

    /** Resume upload slots still available under the active plan's limit. Null = unlimited. */
    public function remainingResumeQuota(): ?int
    {
        $limit = $this->activePlan()?->resume_limit;

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->resumes()->count());
    }
}
