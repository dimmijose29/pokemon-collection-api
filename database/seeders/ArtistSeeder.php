<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Gli artisti vengono sincronizzati da TCGdex tramite SyncCatalogSeeder.
 * Questo seeder è mantenuto per compatibilità ma non fa nulla.
 */
class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        // Gestito da SyncCatalogSeeder → SyncCatalogJob::syncArtists()
    }
}
