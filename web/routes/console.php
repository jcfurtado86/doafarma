<?php

declare(strict_types = 1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Send appointment reminders every 5 minutes
Schedule::command('appointments:send-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Send medication expiration alerts daily at 9:00 AM
Schedule::command('medications:send-expiration-alerts')
    ->dailyAt('09:00')
    ->withoutOverlapping();
