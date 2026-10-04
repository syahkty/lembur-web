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
            $table->text('keperluan')->nullable()->after('keterangan');
            $table->text('alasan_revisi')->nullable()->after('status');
            $table->foreignId('mandor_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lemburs', function (Blueprint $table) {
            $table->dropForeign(['mandor_id']);
            $table->dropColumn(['keperluan', 'alasan_revisi', 'mandor_id']);
        });
    }
};
