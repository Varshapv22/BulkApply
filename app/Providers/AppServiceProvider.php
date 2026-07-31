<?php

namespace App\Providers;

use App\Jobs\SendJobApplication;
use App\Models\JobApplication;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('mail-sending', function (SendJobApplication $job) {
            $application = JobApplication::find($job->jobApplicationId);
            if (! $application) {
                return Limit::none();
            }

            $profile = SendJobApplication::resolveProfileFor($application);
            $max = $profile->max_emails_per_hour;

            return $max > 0
                ? Limit::perHour($max)->by('profile:' . ($application->user_id ?: 'legacy'))
                : Limit::none();
        });

        // Keyed by IP + submitted email/username so one attacker can't burn
        // through a large shared-IP allowance, and one victim email being
        // hammered from many IPs still gets throttled per-IP too.
        RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            $key = strtolower((string) $request->input('email')) . '|' . $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('2fa-challenge', function (\Illuminate\Http\Request $request) {
            return Limit::perMinute(5)->by('2fa:' . $request->ip() . '|' . $request->session()->getId());
        });
    }
}
