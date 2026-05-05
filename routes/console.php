<?php

use App\Console\Commands\RecomputeLoanPar;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FR-LM-032: Nightly PAR recomputation at midnight
Schedule::command(RecomputeLoanPar::class)->dailyAt('00:05')->withoutOverlapping();
