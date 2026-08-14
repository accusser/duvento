<?php

use App\Console\Commands\CheckSslCertificates;
use App\Console\Commands\ExpireTrials;
use App\Console\Commands\SendDeadlineReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::command(CheckSslCertificates::class)->daily();
Schedule::command(SendDeadlineReminders::class)->daily();
Schedule::command(ExpireTrials::class)->daily();
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
