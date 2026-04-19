<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->string('source');        // cardmarket, pokewallet, tcgplayer
            $table->string('price_type');    // trend, low, avg, market, sell
            $table->decimal('price', 10, 2);
            $table->timestamp('recorded_at');

            // Indice per query storiche: "dammi l'andamento di questa carta su cardmarket"
            $table->index(['card_id', 'source', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_prices');
    }
};
