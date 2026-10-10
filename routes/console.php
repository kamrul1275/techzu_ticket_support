<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Check ticket SLA every five minutes.
Schedule::command('tickets:check-sla')
    ->everyFiveMinutes()
    ->withoutOverlapping();