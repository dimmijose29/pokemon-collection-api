<?php

namespace App\Console\Commands;

use App\Jobs\SyncSetsJob;
use Illuminate\Console\Command;

class SyncSetsCommand extends Command
{
    protected $signature   = 'sync:sets
                                {--queue : Dispatcha il job sulla coda invece di eseguirlo in modo sincrono}';

    protected $description = 'Sincronizza tutti i set Pokémon da TCGdex (tutte le lingue nel DB)';

    public function handle(): int
    {
        if ($this->option('queue')) {
            SyncSetsJob::dispatch();
            $this->info('SyncSetsJob dispatchato sulla coda.');
        } else {
            $this->info('Avvio sincronizzazione set (sincrono)...');
            app(SyncSetsJob::class)->handle(app(\App\Services\TcgdexService::class));
            $this->info('Sincronizzazione completata.');
        }

        return Command::SUCCESS;
    }
}
