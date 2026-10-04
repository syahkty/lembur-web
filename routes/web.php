<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LemburController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\StasiunController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Dashboard — semua role, dengan filter tanggal
Route::get('/dashboard', [LemburController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ===== LEMBUR / SPL =====
    Route::get('/lembur/buat', [LemburController::class, 'create'])->name('lembur.create');
    Route::post('/lembur', [LemburController::class, 'store'])->name('lembur.store');
    Route::get('/lembur/{lembur}/edit', [LemburController::class, 'edit'])->name('lembur.edit');
    Route::patch('/lembur/{lembur}', [LemburController::class, 'update'])->name('lembur.update');
    Route::patch('/lembur/{lembur}/acc', [LemburController::class, 'acc'])->name('lembur.acc');
    Route::patch('/lembur/{lembur}/tolak', [LemburController::class, 'tolak'])->name('lembur.tolak');
    Route::delete('/lembur/{lembur}', [LemburController::class, 'destroy'])->name('lembur.destroy');
    Route::get('/lembur/riwayat', [LemburController::class, 'riwayat'])->name('lembur.riwayat');
    Route::get('/lembur/print', [LemburController::class, 'print'])->name('lembur.print');

    // ===== ADMIN: Kelola Karyawan =====
    Route::get('/admin/karyawan', [KaryawanController::class, 'index'])->name('admin.karyawan');
    Route::post('/admin/karyawan', [KaryawanController::class, 'store'])->name('admin.karyawan.store');
    Route::patch('/admin/karyawan/{user}', [KaryawanController::class, 'update'])->name('admin.karyawan.update');
    Route::delete('/admin/karyawan/{user}', [KaryawanController::class, 'destroy'])->name('admin.karyawan.destroy');

    // ===== ADMIN: Kelola Stasiun =====
    Route::get('/admin/stasiun', [StasiunController::class, 'index'])->name('admin.stasiun');
    Route::post('/admin/stasiun', [StasiunController::class, 'store'])->name('admin.stasiun.store');
    Route::patch('/admin/stasiun/{stasiun}', [StasiunController::class, 'update'])->name('admin.stasiun.update');
    Route::delete('/admin/stasiun/{stasiun}', [StasiunController::class, 'destroy'])->name('admin.stasiun.destroy');
});

require __DIR__.'/auth.php';
