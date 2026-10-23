<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Elimina i token Sanctum scaduti da più di un giorno (temporanei 2FA compresi)
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Sincronizzazione settimanale con TCGdex (TCGDEX_SCHEDULED_SYNC=true): prima i set nuovi,
// poi le carte di tutti i set in coda, che aggiornano anche lo snapshot dei prezzi.
// Spenta di default: una sync completa sono decine di migliaia di richieste a TCGdex.
if (config('services.tcgdex.scheduled_sync')) {
    Schedule::command('sync:catalog')->weeklyOn(1, '02:30')->withoutOverlapping();
    Schedule::command('sync:sets')->weeklyOn(1, '03:00')->withoutOverlapping();
    Schedule::command('sync:cards --queue')->weeklyOn(1, '04:00')->withoutOverlapping();
}
