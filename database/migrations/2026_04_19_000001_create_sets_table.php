<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sets', function (Blueprint $table) {
            $table->id();
            $table->string('tcgdex_id')->unique();          // es. "swsh1" — chiave per upsert
            $table->string('name');                          // nome canonico inglese
            $table->string('series')->nullable();            // es. "Sword & Shield"
            $table->unsignedSmallInteger('card_count_official')->default(0); // carte nel set ufficiale
            $table->unsignedSmallInteger('card_count_total')->default(0);    // incluse le segrete
            $table->date('release_date')->nullable();
            $table->string('symbol_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sets');
    }
};
