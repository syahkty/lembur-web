<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LemburController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\StasiunController;
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

        // Cetak Dokumen SPL & LRLK
        Route::get('/lembur/print', [LemburController::class, 'print'])->name('lembur.print');
        Route::get('/lembur/lrlk', [LemburController::class, 'lrlk'])->name('lembur.lrlk');
        Route::get('/lembur/lrlk/print', [LemburController::class, 'printLrlk'])->name('lembur.lrlk.print');
    });

    // ===== KHUSUS ADMINISTRATOR =====
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', fn () => redirect()->route('admin.karyawan'))->name('admin');

        // Admin: Kelola Karyawan
        Route::get('/admin/karyawan', [KaryawanController::class, 'index'])->name('admin.karyawan');
        Route::post('/admin/karyawan', [KaryawanController::class, 'store'])->name('admin.karyawan.store');
        Route::patch('/admin/karyawan/{user}', [KaryawanController::class, 'update'])->name('admin.karyawan.update');
        Route::delete('/admin/karyawan/{user}', [KaryawanController::class, 'destroy'])->name('admin.karyawan.destroy');

        // Admin: Kelola Stasiun
        Route::get('/admin/stasiun', [StasiunController::class, 'index'])->name('admin.stasiun');
        Route::post('/admin/stasiun', [StasiunController::class, 'store'])->name('admin.stasiun.store');
        Route::patch('/admin/stasiun/{stasiun}', [StasiunController::class, 'update'])->name('admin.stasiun.update');
        Route::delete('/admin/stasiun/{stasiun}', [StasiunController::class, 'destroy'])->name('admin.stasiun.destroy');
    });
});

require __DIR__.'/auth.php';
