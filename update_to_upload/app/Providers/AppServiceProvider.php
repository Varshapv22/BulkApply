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
    }
}
