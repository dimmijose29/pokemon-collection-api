<?php
namespace Database\Seeders;

use App\Models\Artist;
use Illuminate\Database\Seeder;

class CardTypeSeeder extends Seeder
{
    public function run(): void
    {
        Artist::factory()->count(10)->create();
    }
}