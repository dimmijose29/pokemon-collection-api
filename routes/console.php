<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Elimina i token Sanctum scaduti da più di un giorno (temporanei 2FA compresi)
Schedule::command('sanctum:prune-expired --hours=24')->daily();
