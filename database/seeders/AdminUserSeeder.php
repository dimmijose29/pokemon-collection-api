<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea l'admin solo se non esiste: un nuovo seed non deve reimpostarne la password.
     * La password arriva da ADMIN_PASSWORD; se manca ne viene generata una casuale.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@admin.it');

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->command->info("Utente admin già presente: {$email}");
            return;
        }

        $password = env('ADMIN_PASSWORD') ?: Str::password(20);

        User::create([
            'email'              => $email,
            'username'           => 'admin',
            'password'           => $password,
            'is_admin'           => true,
            'country'            => 'Italia',
            'preferred_currency' => 'EUR',
        ]);

        $this->command->info("Utente admin creato: {$email}");

        if (! env('ADMIN_PASSWORD')) {
            $this->command->warn("Password generata (salvala ora, non verrà mostrata di nuovo): {$password}");
        }
    }
}
