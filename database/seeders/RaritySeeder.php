<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Le rarità vengono sincronizzate da TCGdex tramite SyncCatalogSeeder.
 * Questo seeder è mantenuto per compatibilità ma non fa nulla.
 */
class RaritySeeder extends Seeder
{
    public function run(): void
    {
        // Gestito da SyncCatalogSeeder → SyncCatalogJob::syncRarities()
    }
}
