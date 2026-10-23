<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - totp_secret diventa text e viene cifrato (cast `encrypted` sul modello)
     * - totp_last_used_timestamp impedisce di riutilizzare lo stesso codice TOTP
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('totp_secret')->nullable()->change();
            $table->unsignedBigInteger('totp_last_used_timestamp')->nullable()->after('totp_secret');
        });

        DB::table('users')->whereNotNull('totp_secret')->orderBy('id')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'totp_secret' => Crypt::encryptString($user->totp_secret),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNotNull('totp_secret')->orderBy('id')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'totp_secret' => Crypt::decryptString($user->totp_secret),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('totp_last_used_timestamp');
            $table->string('totp_secret', 255)->nullable()->change();
        });
    }
};
