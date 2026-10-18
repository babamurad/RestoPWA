<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Courier dispatch: pass unanswered offers on even if a queue job was lost
Schedule::command('couriers:expire-offers')->everyMinute()->withoutOverlapping();

// Backups (spatie/laravel-backup), at night in Ashgabat: prune old archives, make a new one,
// then check in the morning that a fresh healthy backup exists (mails BACKUP_NOTIFICATION_EMAIL if not)
Schedule::command('backup:clean')->dailyAt('01:30')->timezone('Asia/Ashgabat')->onOneServer();
Schedule::command('backup:run')->dailyAt('02:00')->timezone('Asia/Ashgabat')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('09:00')->timezone('Asia/Ashgabat');

// Queue health: failed jobs are kept a week for inspection (php artisan queue:failed),
// and a queue that piles up (worker stopped?) is reported — see AppServiceProvider (QueueBusy)
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('queue:monitor '.config('queue.default').':high,'.config('queue.default').':default --max=50')->everyFiveMinutes();
