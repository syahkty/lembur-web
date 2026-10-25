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
        // MySQL: ubah kolom ENUM menjadi string
        DB::statement("ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT 'karyawan'");
    }

    public function down(): void
    {
        // Kembalikan ke ENUM (hanya role yang sudah ada di data)
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','mandor','karyawan','asisten','askep','ktu','administratur','hrga') NOT NULL DEFAULT 'karyawan'");
    }
};
