<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sincronización automática de comprobantes ARCA — una vez por día a las 7 AM
Schedule::command('arca:sincronizar')->dailyAt('07:00');
