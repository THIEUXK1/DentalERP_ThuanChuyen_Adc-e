<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily appointment reminders at 8:00 AM Vietnam time
Schedule::command('appointments:remind')->dailyAt('08:00')->timezone('Asia/Ho_Chi_Minh');
