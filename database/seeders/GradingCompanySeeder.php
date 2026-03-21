<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradingCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grading_companies = [
            'PSA',
            'BGS',
            'CGC',
            'SGC',
            'TAG',
            'HGA',
            'GRAAD',
            'AiGrading',
            'Eur0grading',
            'ACE Grading',
            'PCA',
            'AP Grading',
            'ARS'
        ];

        foreach ($grading_companies as $grading_company) {
            DB::table('grading_companies')->updateOrInsert(
                ['name' => $grading_company],
                ['name' => $grading_company]
            );
        }
    }
}
