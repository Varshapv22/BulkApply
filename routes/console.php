<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Self-heal applications stuck in "queued" from a crashed worker or abandoned batch (every 5 minutes)
Schedule::command('app:sweep-stale-queued')->everyFiveMinutes();

// Dispatch follow-up emails for overdue applications (hourly)
Schedule::command('app:dispatch-followups')->hourly();

// Re-run active saved searches and notify users of new listings (every 3 hours)
Schedule::command('app:check-saved-search-alerts')->everyThreeHours();
