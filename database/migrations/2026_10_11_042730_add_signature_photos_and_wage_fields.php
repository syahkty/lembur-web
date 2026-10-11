<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('tanda_tangan')->nullable()->after('remember_token');
            $table->decimal('gaji_pokok', 14, 2)->nullable()->after('tanda_tangan');
            $table->decimal('tarif_lembur_per_jam', 12, 2)->nullable()->after('gaji_pokok');
        });

        Schema::table('lemburs', function (Blueprint $table) {
            $table->longText('tanda_tangan')->nullable()->after('status');
            $table->string('foto_dokumentasi')->nullable()->after('tanda_tangan');
            $table->decimal('jam_lembur_dibayar', 5, 2)->nullable()->after('foto_dokumentasi');
            $table->decimal('upah_lembur', 14, 2)->nullable()->after('jam_lembur_dibayar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lemburs', function (Blueprint $table) {
            $table->dropColumn(['tanda_tangan', 'foto_dokumentasi', 'jam_lembur_dibayar', 'upah_lembur']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tanda_tangan', 'gaji_pokok', 'tarif_lembur_per_jam']);
        });
    }
};
