<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// This single line is the entire "cron system" the crawler needs.
// On shared hosting (cPanel etc.), the ONE cron entry to add is:
//   * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
// Laravel's scheduler dispatches this on that cadence — no separate cron line
// for the crawler itself, and no queue worker/Supervisor/Redis needed.
Schedule::command('crawler:process')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();
