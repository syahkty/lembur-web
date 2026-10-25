<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LemburController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\StasiunController;
use App\Http\Controllers\DokumenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Panduan SOP & Alur Kerja
Route::get('/panduan', function () {
    return response()->file(public_path('panduan.html'));
})->name('panduan');

// Dashboard — semua role yang sudah login
Route::get('/dashboard', [LemburController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Riwayat Lembur (Karyawan, Mandor, Admin)
    Route::get('/lembur/riwayat', [LemburController::class, 'riwayat'])->name('lembur.riwayat');

    // Aksi Karyawan terhadap SPL (ACC, Upload Foto, Tolak)
    Route::patch('/lembur/{lembur}/acc', [LemburController::class, 'acc'])->name('lembur.acc');
    Route::post('/lembur/{lembur}/foto', [LemburController::class, 'uploadFoto'])->name('lembur.foto');
    Route::patch('/lembur/{lembur}/tolak', [LemburController::class, 'tolak'])->name('lembur.tolak');

    // ===== KHUSUS MANDOR & ADMIN =====
    Route::middleware('role:mandor,admin')->group(function () {
        Route::get('/lembur/buat', [LemburController::class, 'create'])->name('lembur.create');
        Route::post('/lembur', [LemburController::class, 'store'])->name('lembur.store');
        Route::get('/lembur/{lembur}/edit', [LemburController::class, 'edit'])->name('lembur.edit');
        Route::patch('/lembur/{lembur}', [LemburController::class, 'update'])->name('lembur.update');
        Route::delete('/lembur/{lembur}', [LemburController::class, 'destroy'])->name('lembur.destroy');

        // Lihat data LRLK (tanpa cetak untuk mandor)
        Route::get('/lembur/lrlk', [LemburController::class, 'lrlk'])->name('lembur.lrlk');
    });

    // ===== KHUSUS ADMINISTRATOR =====
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', fn () => redirect()->route('admin.akun'))->name('admin');

        // Cetak Dokumen SPL & LRLK (khusus admin)
        Route::get('/lembur/print', [LemburController::class, 'print'])->name('lembur.print');
        Route::get('/lembur/lrlk/print', [LemburController::class, 'printLrlk'])->name('lembur.lrlk.print');

        // Admin: Kelola Akun
        Route::get('/admin/akun', [KaryawanController::class, 'index'])->name('admin.akun');
        Route::post('/admin/akun', [KaryawanController::class, 'store'])->name('admin.akun.store');
        Route::patch('/admin/akun/{user}', [KaryawanController::class, 'update'])->name('admin.akun.update');
        Route::delete('/admin/akun/{user}', [KaryawanController::class, 'destroy'])->name('admin.akun.destroy');
        Route::redirect('/admin/karyawan', '/admin/akun');

        // Admin: Kelola Stasiun
        Route::get('/admin/stasiun', [StasiunController::class, 'index'])->name('admin.stasiun');
        Route::post('/admin/stasiun', [StasiunController::class, 'store'])->name('admin.stasiun.store');
        Route::patch('/admin/stasiun/{stasiun}', [StasiunController::class, 'update'])->name('admin.stasiun.update');
        Route::delete('/admin/stasiun/{stasiun}', [StasiunController::class, 'destroy'])->name('admin.stasiun.destroy');
    });

    // ===== KHUSUS PENANDATANGAN DOKUMEN (Asisten, Askep, KTU, Administratur, HRGA) =====
    Route::middleware('role:asisten,askep,ktu,administratur,hrga')->group(function () {
        Route::get('/dokumen', [DokumenController::class, 'index'])->name('dokumen.index');
        Route::post('/dokumen/tanda-tangan', [DokumenController::class, 'simpanTandaTangan'])->name('dokumen.ttd');
        // Preview bentuk cetak (read-only, tanpa opsi cetak)
        Route::get('/dokumen/preview/spl', [DokumenController::class, 'printSpl'])->name('dokumen.preview.spl');
        Route::get('/dokumen/preview/lrlk', [DokumenController::class, 'printLrlk'])->name('dokumen.preview.lrlk');
    });
});

require __DIR__.'/auth.php';
