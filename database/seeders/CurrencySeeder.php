<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class currencieseeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD','name' => 'US Dollar','symbol' => '$','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'EUR','name' => 'Euro','symbol' => '€','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'GBP','name' => 'British Pound','symbol' => '£','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'JPY','name' => 'Japanese Yen','symbol' => '¥','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'CAD','name' => 'Canadian Dollar','symbol' => 'C$','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'AUD','name' => 'Australian Dollar','symbol' => 'A$','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->insert($currency);
        }
    }
}
