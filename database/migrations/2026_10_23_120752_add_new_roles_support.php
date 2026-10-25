<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom tanda_tangan ke users untuk role-role baru
     * (asisten, askep, ktu, administratur, hrga)
     * Kolom tanda_tangan sudah ada, jadi migration ini hanya dokumentasi penambahan role.
     * Role disimpan sebagai string di kolom 'role' yang sudah ada.
     */
    public function up(): void
    {
        // Role baru (asisten, askep, ktu, administratur, hrga) sudah bisa
        // langsung disimpan di kolom role yang sudah ada (varchar/string).
        // Tidak perlu perubahan skema tambahan di tabel users.
        // Migration ini berfungsi sebagai catatan/dokumentasi.
    }

    public function down(): void
    {
        //
    }
};
