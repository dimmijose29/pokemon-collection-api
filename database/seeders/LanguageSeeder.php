<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['code' => 'en','name' => 'English','country_code' => 'GB','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'fr','name' => 'Français','country_code' => 'FR','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'de','name' => 'Deutsch','country_code' => 'DE','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'es','name' => 'Español','country_code' => 'ES','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'it','name' => 'Italiano','country_code' => 'IT','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'pt','name' => 'Português','country_code' => 'PT','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'ja','name' => '日本語 (Japanese)','country_code' => 'JP','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'ko','name' => '한국어 (Korean)','country_code' => 'KR','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'zh-Hant','name' => '繁體中文 (Traditional)','country_code' => 'TW','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
            ['code' => 'zh-Hans','name' => '简体中文 (Simplified)', 'country_code' => 'CN','created_at' => \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()],
        ];

        foreach ($languages as $lang) {
            DB::table('languages')->insert($lang);
        }
    }
}
