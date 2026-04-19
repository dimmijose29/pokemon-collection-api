<?php

namespace Database\Seeders;

use App\Jobs\SyncSetsJob;
use App\Services\TcgdexService;
use Illuminate\Database\Seeder;

/**
 * Importa tutti i set Pokémon da TCGdex con le relative traduzioni.
 * Richiede che le lingue siano già presenti nel DB (nessuna dipendenza diretta,
 * ma le traduzioni verranno saltate se la tabella languages è vuota).
 */
class SyncSetsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Sincronizzazione set da TCGdex (~2 minuti)...');

        app(SyncSetsJob::class)->handle(app(TcgdexService::class));

        $this->command->info('Set sincronizzati.');
    }
}
