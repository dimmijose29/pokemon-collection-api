<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Aggiungi series_id nullable
        Schema::table('sets', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('name')->constrained('series')->nullOnDelete();
        });

        // 2. Migra i dati: crea una Series per ogni valore distinto di series
        $distinctSeries = DB::table('sets')
            ->whereNotNull('series')
            ->distinct()
            ->pluck('series');

        foreach ($distinctSeries as $seriesName) {
            $seriesId = DB::table('series')->insertGetId([
                'name'       => $seriesName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('sets')
                ->where('series', $seriesName)
                ->update(['series_id' => $seriesId]);
        }

        // 3. Rimuovi la colonna series varchar
        Schema::table('sets', function (Blueprint $table) {
            $table->dropColumn('series');
        });
    }

    public function down(): void
    {
        Schema::table('sets', function (Blueprint $table) {
            $table->string('series')->nullable()->after('name');
        });

        // Ripristina i dati
        $sets = DB::table('sets')->whereNotNull('series_id')->get();
        foreach ($sets as $set) {
            $series = DB::table('series')->find($set->series_id);
            if ($series) {
                DB::table('sets')->where('id', $set->id)->update(['series' => $series->name]);
            }
        }

        Schema::table('sets', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropColumn('series_id');
        });
    }
};
