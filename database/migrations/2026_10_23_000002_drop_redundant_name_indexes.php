<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indici semplici creati accanto a un unique sulla stessa colonna:
     * il vincolo unique è già un indice, quindi questi sono doppioni.
     */
    private array $indexes = [
        'languages'         => 'code',
        'rarities'          => 'name',
        'card_types'        => 'name',
        'artists'           => 'name',
        'grading_companies' => 'name',
        'conditions'        => 'name',
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $column) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex([$column]));
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $column) {
            Schema::table($table, fn (Blueprint $t) => $t->index($column));
        }
    }
};
