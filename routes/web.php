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

    Route::get('/dashboard', [KaryawanController::class, 'dashboard'])
         ->name('karyawan.dashboard');
    Route::get('/unggah', [KaryawanController::class, 'unggah']);
    Route::get('/riwayat', [KaryawanController::class, 'riwayat']);
    Route::get('/izin', [KaryawanController::class, 'izin']);
    Route::get('/profil', [KaryawanController::class, 'profil']);

    // 1. Rute GET (HAPUS {id} dari sini)
    Route::get('/absensi/unggah/{type}', [KaryawanController::class, 'showUploadForm'])
         ->name('karyawan.absensi.unggah');

    // 2. Rute untuk MENYIMPAN foto (POST) - Ini yang dicari!
    //    URL-nya akan jadi: /karyawan/absensi/simpan-foto
    Route::post('/absensi/simpan-foto', [KaryawanController::class, 'storeFoto'])
         ->name('karyawan.absensi.storeFoto');
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

    Route::get('/geofencing', [AdminController::class, 'showGeofencing'])->name('admin.geofencing.show');

    // Rute untuk MENYIMPAN pengaturan geofencing
    Route::post('/geofencing/save', [AdminController::class, 'saveGeofencing'])->name('admin.geofencing.save');
    Route::get('/validasi', [AdminController::class, 'showValidasiPage'])
         ->name('admin.validasi.show');

    // 2. Rute POST untuk menyimpan approve/reject
    Route::post('/validasi/simpan', [AdminController::class, 'submitValidasi'])
         ->name('admin.validasi.submit');
});
