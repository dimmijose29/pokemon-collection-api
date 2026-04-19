<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'EUR', 'symbol' => '€'],
            ['code' => 'USD', 'symbol' => '$'],
            ['code' => 'GBP', 'symbol' => '£'],
            ['code' => 'JPY', 'symbol' => '¥'],
            ['code' => 'CAD', 'symbol' => 'C$'],
            ['code' => 'AUD', 'symbol' => 'A$'],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $currency['code']],
                array_merge($currency, [
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ])
            );
        }
    }
}
