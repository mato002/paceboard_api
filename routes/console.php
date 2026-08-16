<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('paceboard:prune-reports')->hourly();
Schedule::command('paceboard:recalculate-leaderboards')->dailyAt('00:15');
