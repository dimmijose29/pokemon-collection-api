<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('tcgdex_id')->unique();           // es. "swsh1-136"
            $table->foreignId('set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->nullable()->constrained('artists')->nullOnDelete();
            $table->foreignId('rarity_id')->nullable()->constrained('rarities')->nullOnDelete();
            $table->foreignId('card_type_id')->nullable()->constrained('card_types')->nullOnDelete();
            $table->string('local_id');                      // numero nella serie, es. "136"
            $table->string('image_url')->nullable();
            $table->string('stage')->nullable();             // Basic, Stage 1, Stage 2, ecc.
            $table->json('energy_types')->nullable();        // ["Fire", "Water"] — tipi energetici
            $table->timestamps();
            $table->softDeletes();

            $table->index(['set_id', 'local_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
