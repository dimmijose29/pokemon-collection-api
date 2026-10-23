<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uno snapshot di prezzo per carta, fonte, tipo e giorno:
     * rilanciare la sync (o un retry della coda) aggiorna il valore del giorno invece di duplicarlo.
     * La valuta arriva da TCGdex (pricing.cardmarket.unit).
     */
    public function up(): void
    {
        Schema::table('card_prices', function (Blueprint $table) {
            $table->string('currency', 3)->default('EUR')->after('price');
            $table->date('recorded_on')->nullable()->after('recorded_at');
        });

        DB::table('card_prices')->update(['recorded_on' => DB::raw('DATE(recorded_at)')]);

        // Duplicati dello stesso giorno: resta il più recente
        $duplicates = DB::table('card_prices')
            ->select('card_id', 'source', 'price_type', 'recorded_on', DB::raw('MAX(id) as keep_id'))
            ->groupBy('card_id', 'source', 'price_type', 'recorded_on')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $d) {
            DB::table('card_prices')
                ->where(['card_id' => $d->card_id, 'source' => $d->source, 'price_type' => $d->price_type, 'recorded_on' => $d->recorded_on])
                ->where('id', '<>', $d->keep_id)
                ->delete();
        }

        Schema::table('card_prices', function (Blueprint $table) {
            $table->date('recorded_on')->nullable(false)->change();
            // Copre anche le query "ultimo prezzo trend di una carta"
            $table->unique(['card_id', 'source', 'price_type', 'recorded_on'], 'card_prices_daily_unique');
            $table->dropIndex(['card_id', 'source', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('card_prices', function (Blueprint $table) {
            $table->index(['card_id', 'source', 'recorded_at']);
            $table->dropUnique('card_prices_daily_unique');
            $table->dropColumn(['currency', 'recorded_on']);
        });
    }
};
