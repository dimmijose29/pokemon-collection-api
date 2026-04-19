<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.it'],
            [
                'username'   => 'admin',
                'password'   => Hash::make('password123'),
                'is_admin'   => true,
                'country'    => 'Italia',
                'preferred_currency' => 'EUR',
            ]
        );

        $this->command->info('Utente admin creato: admin@admin.it / password123');
    }
}
