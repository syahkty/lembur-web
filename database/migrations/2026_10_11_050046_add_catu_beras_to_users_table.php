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
            $table->string('status_pernikahan', 10)->nullable()->default('TK')->after('gaji_pokok');
            $table->decimal('jatah_beras_kg', 5, 2)->default(15.00)->after('status_pernikahan');
            $table->decimal('harga_beras_per_kg', 10, 2)->default(14000.00)->after('jatah_beras_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status_pernikahan', 'jatah_beras_kg', 'harga_beras_per_kg']);
        });
    }
};
