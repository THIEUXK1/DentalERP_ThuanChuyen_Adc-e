<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily appointment reminders at 8:00 AM Vietnam time
Schedule::command('appointments:remind')->dailyAt('08:00')->timezone('Asia/Ho_Chi_Minh');

// Pull attendance logs from all active ZKTeco devices nightly at 23:30
Schedule::command('attendance:sync')->dailyAt('23:30')->timezone('Asia/Ho_Chi_Minh');

// Execute pending deletions every minute
Schedule::command('pending-deletions:execute')->everyMinute();
