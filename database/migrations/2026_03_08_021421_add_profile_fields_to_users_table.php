<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('username');
            $table->string('preferred_currency', 10)->nullable()->after('country');
            $table->year('birth_year')->nullable()->after('preferred_currency');
            $table->string('avatar_url', 255)->nullable()->after('birth_year');
            $table->text('bio')->nullable()->after('avatar_url');

            // Indici utili (opzionali)
            $table->index('preferred_currency');
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'preferred_currency',
                'birth_year',
                'avatar_url',
                'bio',
            ]);
        });
    }
};