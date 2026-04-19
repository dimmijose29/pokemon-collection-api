<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('set_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['set_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('set_translations');
    }
};
