<?php
//TODO aggiungere tipi mancanti
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CardTypeSeeder extends Seeder
{
    public function run(): void
    {
        $card_types = [
            'Pokémon', // per carte senza rarità specifica
            'Trainer',
            'Energy',
            'Object',
        ];

        foreach ($card_types as $card_type) {
            DB::table('card_types')->updateOrInsert(
                ['name' => $card_type],
                ['name' => $card_type]
            );
        }
    }
}