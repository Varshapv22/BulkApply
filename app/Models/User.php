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
        'trial_ends_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'two_factor_recovery_codes',
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
            'trial_ends_at' => 'datetime',
            'last_login_at' => 'datetime',
            'google2fa_enabled' => 'boolean',
            'google2fa_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    public function coverLetters()
    {
        return $this->hasMany(CoverLetter::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function planPaymentRequests()
    {
        return $this->hasMany(PlanPaymentRequest::class);
    }

    public function savedSearches()
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    private ?Subscription $activeSubscriptionCache = null;
    private bool $activeSubscriptionResolved = false;

    /** Memoized per-instance — called repeatedly per request (shared Inertia props, quota checks, trial middleware). */
    public function activeSubscription(): ?Subscription
    {
        if (! $this->activeSubscriptionResolved) {
            $this->activeSubscriptionCache = $this->subscriptions()->active()->latest('starts_at')->first();
            $this->activeSubscriptionResolved = true;
        }

        return $this->activeSubscriptionCache;
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

        $since = $subscription->starts_at ?? $subscription->created_at;

        $sentSinceSubscription = JobApplication::where('user_id', $this->id)
            ->where('status', JobApplication::STATUS_SENT)
            ->where('sent_at', '>=', $since)
            ->count();

        // Also count applications already queued (dispatched but not yet
        // sent) so quota can't be bypassed by queuing repeatedly while sends
        // are held by a closed sending window.
        $queuedSinceSubscription = JobApplication::where('user_id', $this->id)
            ->where('status', JobApplication::STATUS_QUEUED)
            ->where('updated_at', '>=', $since)
            ->count();

        return max(0, $limit - $sentSinceSubscription - $queuedSinceSubscription);
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

    /** Cover letter slots still available under the active plan's limit. Null = unlimited. */
    public function remainingCoverLetterQuota(): ?int
    {
        $limit = $this->activePlan()?->cover_letter_limit;

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->coverLetters()->count());
    }
}
