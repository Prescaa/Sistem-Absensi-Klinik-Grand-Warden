<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return redirect('/login');
});

// --- Rute Autentikasi ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'handleLogin']);
Route::get('/logout', [AuthController::class, 'logout']);


// --- Rute Karyawan (Pegawai) ---
// Ditambahkan 'role:karyawan' untuk memastikan hanya Karyawan (case-insensitive)
// yang bisa mengakses rute ini.
Route::middleware(['auth', 'role:karyawan'])->group(function () {

    Route::get('/dashboard', [KaryawanController::class, 'dashboard']);
    Route::get('/unggah', [KaryawanController::class, 'unggah']);
    Route::get('/riwayat', [KaryawanController::class, 'riwayat']);
    Route::get('/izin', [KaryawanController::class, 'izin']);
    Route::get('/profil', [KaryawanController::class, 'profil']);

});

// -----------------------------------------------------------------
// --- Rute Admin ---
// -----------------------------------------------------------------
// Middleware 'role:admin' akan memastikan hanya Admin (case-insensitive)
// yang bisa mengakses rute ini.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {

    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    Route::get('/validasi', [AdminController::class, 'showValidasi']);
    Route::post('/validasi/approve', [AdminController::class, 'handleApprove']);
    Route::post('/validasi/reject', [AdminController::class, 'handleReject']);

    // --- Rute CRUD Lengkap untuk Manajemen Karyawan ---
    Route::get('/manajemen-karyawan', [AdminController::class, 'showManajemenKaryawan']);
    Route::post('/manajemen-karyawan/store', [AdminController::class, 'storeKaryawan']);

    // Rute baru untuk Update (dihubungkan ke modal Edit)
    Route::put('/manajemen-karyawan/update/{id}', [AdminController::class, 'updateKaryawan']);
    
    // Rute baru untuk Delete (dihubungkan ke modal Hapus)
    Route::delete('/manajemen-karyawan/destroy/{id}', [AdminController::class, 'destroyKaryawan']);
    // --- Akhir Rute CRUD ---

    Route::get('/laporan', [AdminController::class, 'showLaporan']);
    Route::post('/laporan/export', [AdminController::class, 'exportLaporan']);

});
