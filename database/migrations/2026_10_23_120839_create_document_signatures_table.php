<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel untuk menyimpan tanda tangan digital para penandatangan dokumen
     * (Asisten, Askep, KTU, Administratur, HRGA) per tanggal/periode dokumen.
     */
    public function up(): void
    {
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Kolom tanda tangan (base64 PNG atau path storage)
            $table->longText('tanda_tangan');
            // Tanggal dokumen yang ditandatangani (bisa null = berlaku umum/permanen)
            $table->date('tanggal')->nullable()->index();
            // Jenis dokumen: 'spl', 'lrlk', atau null = semua
            $table->string('jenis_dokumen', 10)->nullable();
            $table->timestamps();

            // Satu user hanya punya satu TTD per tanggal per jenis dokumen
            $table->unique(['user_id', 'tanggal', 'jenis_dokumen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};
