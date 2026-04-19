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
        // I codici devono corrispondere a TcgdexService::SUPPORTED_LANGS
        $languages = [
            ['code' => 'en',    'name' => 'English',           'country_code' => 'GB'],
            ['code' => 'it',    'name' => 'Italiano',          'country_code' => 'IT'],
            ['code' => 'fr',    'name' => 'Français',          'country_code' => 'FR'],
            ['code' => 'de',    'name' => 'Deutsch',           'country_code' => 'DE'],
            ['code' => 'es',    'name' => 'Español',           'country_code' => 'ES'],
            ['code' => 'pt',    'name' => 'Português',         'country_code' => 'PT'],
            ['code' => 'ja',    'name' => '日本語',             'country_code' => 'JP'],
            ['code' => 'ko',    'name' => '한국어',             'country_code' => 'KR'],
            ['code' => 'zh-CN', 'name' => '中文(简体)',         'country_code' => 'CN'],
            ['code' => 'zh-TW', 'name' => '中文(繁體)',         'country_code' => 'TW'],
        ];

        foreach ($languages as $lang) {
            DB::table('languages')->updateOrInsert(
                ['code' => $lang['code']],
                array_merge($lang, [
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ])
            );
        }
    }
}
