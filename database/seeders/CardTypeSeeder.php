<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * I tipi carta vengono sincronizzati da TCGdex tramite SyncCatalogSeeder.
 * Questo seeder è mantenuto per compatibilità ma non fa nulla.
 */
class CardTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Gestito da SyncCatalogSeeder → SyncCatalogJob::syncCardTypes()
    }
}
