<?php

namespace Database\Seeders;

use App\Jobs\SyncCatalogJob;
use App\Services\TcgdexService;
use Illuminate\Database\Seeder;

/**
 * Popola rarità, tipi carta e artisti da TCGdex.
 * Deve girare PRIMA di SyncSetsSeeder e sync:cards.
 */
class SyncCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Sincronizzazione catalogo (rarità, tipi carta, artisti)...');

        app(SyncCatalogJob::class)->handle(app(TcgdexService::class));

        $this->command->info('Catalogo sincronizzato.');
    }
}
