<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Courier dispatch: pass unanswered offers on even if a queue job was lost
Schedule::command('couriers:expire-offers')->everyMinute()->withoutOverlapping();
