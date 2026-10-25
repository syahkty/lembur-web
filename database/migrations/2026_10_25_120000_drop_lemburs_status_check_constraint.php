<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Hapus check constraint PostgreSQL pada kolom status agar bisa menyimpan 'tidak_lembur', dll.
            DB::statement("ALTER TABLE lemburs DROP CONSTRAINT IF EXISTS lemburs_status_check");
            DB::statement("ALTER TABLE lemburs ALTER COLUMN status TYPE VARCHAR(50) USING status::VARCHAR(50)");
            DB::statement("ALTER TABLE lemburs ALTER COLUMN status SET DEFAULT 'pending'");
        } elseif ($driver === 'sqlite') {
            // SQLite
        } else {
            // MySQL
            DB::statement("ALTER TABLE lemburs MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
