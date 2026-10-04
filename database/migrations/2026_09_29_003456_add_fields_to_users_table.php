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
            $table->string('no_employe')->nullable()->after('name');
            $table->foreignId('stasiun_id')->nullable()->after('role')->constrained('stasions')->nullOnDelete();
            $table->integer('shift')->nullable()->after('stasiun_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['stasiun_id']);
            $table->dropColumn(['no_employe', 'stasiun_id', 'shift']);
        });
    }
};
