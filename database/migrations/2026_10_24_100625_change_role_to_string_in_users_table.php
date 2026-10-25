<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ubah kolom 'role' dari ENUM terbatas menjadi VARCHAR(50)
     * agar bisa menampung role-role baru:
     * asisten, askep, ktu, administratur, hrga
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL syntax
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(50) USING role::VARCHAR(50)");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'karyawan'");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET NOT NULL");
        } elseif ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 50)->default('karyawan')->change();
            });
        } else {
            // MySQL / MariaDB syntax
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT 'karyawan'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(50)");
        } elseif ($driver === 'sqlite') {
            // No-op for sqlite
        } else {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','mandor','karyawan','asisten','askep','ktu','administratur','hrga') NOT NULL DEFAULT 'karyawan'");
        }
    }
};
