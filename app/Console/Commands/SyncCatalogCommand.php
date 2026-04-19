<?php

namespace App\Console\Commands;

use App\Jobs\SyncCatalogJob;
use Illuminate\Console\Command;

class SyncCatalogCommand extends Command
{
    protected $signature   = 'sync:catalog
                                {--queue : Dispatcha il job sulla coda invece di eseguirlo in modo sincrono}';

    protected $description = 'Sincronizza rarità, tipi carta e artisti da TCGdex. Eseguire PRIMA di sync:cards.';

    public function handle(): int
    {
        if ($this->option('queue')) {
            SyncCatalogJob::dispatch();
            $this->info('SyncCatalogJob dispatchato sulla coda.');
        } else {
            $this->info('Sincronizzazione catalogo (rarità, tipi carta, artisti)...');
            app(SyncCatalogJob::class)->handle(app(\App\Services\TcgdexService::class));
            $this->info('Sincronizzazione catalogo completata.');
        }

        return Command::SUCCESS;
    }
}
