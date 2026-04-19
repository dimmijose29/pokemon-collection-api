<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Utente admin
            AdminUserSeeder::class,

            // 2. Dati statici (nessuna dipendenza esterna)
            LanguageSeeder::class,
            ConditionSeeder::class,
            CurrencySeeder::class,
            GradingCompanySeeder::class,

            // 3. Catalogo TCGdex: rarità, tipi carta, artisti
            //    Deve girare PRIMA dei set, perché i set linking agli artisti
            SyncCatalogSeeder::class,

            // 4. Set Pokémon con traduzioni (~2 min)
            SyncSetsSeeder::class,

            // Le carte vengono sincronizzate separatamente dopo il seeding:
            // php artisan sync:cards
            // (troppo lente per stare nel seeder — 208 set × N carte ciascuno)
        ]);

        $this->command->newLine();
        $this->command->info('✓ Database pronto.');
        $this->command->warn('  Per importare le carte esegui: php artisan sync:cards');
        $this->command->warn('  Per un singolo set:           php artisan sync:cards swsh1');
    }
}
