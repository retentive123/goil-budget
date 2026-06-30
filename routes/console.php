<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run --type=scheduled')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->emailOutputOnFailure(config('mail.from.address'));

// Weekly full backup on Sunday at 1am
Schedule::command('backup:run --type=scheduled')
    ->weekly()
    ->sundays()
    ->at('01:00')
    ->withoutOverlapping();
