<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process queued emails within the sending window (every 5 minutes)
Schedule::command('app:send-scheduled')->everyFiveMinutes();

// Dispatch follow-up emails for overdue applications (hourly)
Schedule::command('app:dispatch-followups')->hourly();
