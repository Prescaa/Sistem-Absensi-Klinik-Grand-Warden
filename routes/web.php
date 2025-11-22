<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManajemenController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/login');
});

// --- Rute Autentikasi ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'handleLogin']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');


// --- Rute Karyawan (Pegawai) ---
Route::middleware(['auth', 'role:karyawan'])->group(function () {

    Route::get('/dashboard', [KaryawanController::class, 'dashboard'])->name('karyawan.dashboard');
    Route::get('/unggah', [KaryawanController::class, 'unggah'])->name('karyawan.unggah');
    Route::get('/riwayat', [KaryawanController::class, 'riwayat'])->name('karyawan.riwayat');
    Route::get('/izin', [KaryawanController::class, 'izin'])->name('karyawan.izin');
    Route::get('/profil', [KaryawanController::class, 'profil'])->name('karyawan.profil');

    // Rute Update & Hapus Foto Karyawan
    Route::put('/profil/update', [KaryawanController::class, 'updateProfil'])->name('karyawan.profil.update');
    Route::delete('/profil/hapus-foto', [KaryawanController::class, 'deleteFotoProfil'])->name('karyawan.profil.deleteFoto');

    // Rute Absensi
    Route::get('/absensi/unggah/{type}', [KaryawanController::class, 'showUploadForm'])->name('karyawan.absensi.unggah');
    Route::post('/karyawan/absensi/check-exif', [KaryawanController::class, 'checkExif'])->name('karyawan.absensi.checkExif');
    Route::post('/absensi/simpan-foto', [KaryawanController::class, 'storeFoto'])->middleware('throttle:5,1')->name('karyawan.absensi.storeFoto');
    
    // Rute Izin
    Route::post('/izin/simpan', [KaryawanController::class, 'storeIzin'])->name('karyawan.izin.store');
});


// --- GROUP ADMIN (Fokus Operasional) ---
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard'); 
    
    // Validasi
    Route::get('/validasi', [AdminController::class, 'showValidasiPage'])->name('admin.validasi.show');
    Route::post('/validasi/simpan', [AdminController::class, 'submitValidasi'])->name('admin.validasi.submit');
    Route::post('/validasi/izin/simpan', [AdminController::class, 'submitValidasiIzin'])->name('admin.validasi.izin.submit');

    // CRUD Karyawan
    Route::get('/manajemen-karyawan', [AdminController::class, 'showManajemenKaryawan'])->name('admin.karyawan.index');
    Route::post('/manajemen-karyawan/store', [AdminController::class, 'storeKaryawan'])->name('admin.karyawan.store');
    Route::put('/manajemen-karyawan/update/{id}', [AdminController::class, 'updateKaryawan'])->name('admin.karyawan.update');
    Route::delete('/manajemen-karyawan/destroy/{id}', [AdminController::class, 'destroyKaryawan'])->name('admin.karyawan.destroy');

    // Geofencing
    Route::get('/geofencing', [AdminController::class, 'showGeofencing'])->name('admin.geofencing.show');
    Route::post('/geofencing/save', [AdminController::class, 'saveGeofencing'])->name('admin.geofencing.save');

    // Laporan
    Route::get('/laporan', [AdminController::class, 'showLaporan'])->name('admin.laporan.show'); 
    Route::post('/laporan/export', [AdminController::class, 'exportLaporan'])->name('admin.laporan.export');

    // Profil Admin (Shared Controller)
    Route::get('/profil', [ProfileController::class, 'index'])->name('admin.profil');
    Route::post('/profil', [ProfileController::class, 'update'])->name('admin.profil.update');
    
});

// --- GROUP MANAJEMEN ---
Route::middleware(['auth', 'role:manajemen'])->prefix('manajemen')->group(function () {
    Route::get('/dashboard', [ManajemenController::class, 'dashboard'])->name('manajemen.dashboard');
    Route::post('/laporan/export', [ManajemenController::class, 'exportLaporan'])->name('manajemen.laporan.export');
    Route::get('/profil', [ProfileController::class, 'index'])->name('manajemen.profil');
    Route::post('/profil', [ProfileController::class, 'update'])->name('manajemen.profil.update');
    Route::delete('/profil/hapus-foto', [ProfileController::class, 'deleteFotoAdmin'])->name('manajemen.profil.deleteFoto');
});