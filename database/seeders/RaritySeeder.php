<?php
//TODO aggiungere rarità mancanti e di lingue orientali (SAR, AR, etc.)
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RaritySeeder extends Seeder
{
    public function run(): void
    {
        $rarities = [
            'Common',
            'Uncommon',
            'Rare',
            'Rare Holo',
            'Ultra Rare',
            'Secret Rare',
            'Rainbow Rare',
            'Gold Rare',
            'EX',
            'GX',
            'V',
            'VMAX',
            'VSTAR',
            'TAG TEAM',
            'Radiant Rare',
            'Illustration Rare',
            'Special Illustration Rare',
            'Hyper Rare',
        ];

        foreach ($rarities as $rarity) {
            DB::table('rarities')->updateOrInsert(
                ['name' => $rarity],
                ['name' => $rarity]
            );
        }
    }
}