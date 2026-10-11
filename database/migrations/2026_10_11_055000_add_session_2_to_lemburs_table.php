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
        Schema::table('lemburs', function (Blueprint $table) {
            $table->time('jam_mulai_2')->nullable()->after('jam_selesai');
            $table->time('jam_selesai_2')->nullable()->after('jam_mulai_2');
            $table->text('keterangan_2')->nullable()->after('keterangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lemburs', function (Blueprint $table) {
            $table->dropColumn(['jam_mulai_2', 'jam_selesai_2', 'keterangan_2']);
        });
    }
};
