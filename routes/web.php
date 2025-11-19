<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;

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
Route::post('/login', [AuthController::class, 'handleLogin'])
    ->middleware('throttle:5,1');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');


// --- Rute Karyawan (Pegawai) ---
Route::middleware(['auth', 'role:karyawan'])->group(function () {

    Route::get('/dashboard', [KaryawanController::class, 'dashboard'])
         ->name('karyawan.dashboard');

    Route::get('/unggah', [KaryawanController::class, 'unggah'])->name('karyawan.unggah');
    Route::get('/riwayat', [KaryawanController::class, 'riwayat'])->name('karyawan.riwayat');
    Route::get('/izin', [KaryawanController::class, 'izin'])->name('karyawan.izin');
    Route::get('/profil', [KaryawanController::class, 'profil'])->name('karyawan.profil');

    // --- PERBAIKAN DI SINI ---
    // Menggunakan KaryawanController untuk menampilkan view (ini sudah benar di kode kamu)
    // 1. Rute Menampilkan Halaman Profil (GET)
    Route::get('/profil', [KaryawanController::class, 'profil'])->name('karyawan.profil');

    // 2. Rute Memproses Update Profil (PUT)
    // - Menggunakan method PUT (sesuai form @method('PUT'))
    // - URL dibedakan menjadi '/profil/update'
    // - Menggunakan KaryawanController fungsi updateProfil
    Route::put('/profil/update', [KaryawanController::class, 'updateProfil'])->name('karyawan.profil.update');
    Route::delete('/profil/hapus-foto', [KaryawanController::class, 'deleteFotoProfil'])->name('karyawan.profil.deleteFoto');
    // 1. Rute Halaman Upload Absensi
    Route::get('/absensi/unggah/{type}', [KaryawanController::class, 'showUploadForm'])
         ->name('karyawan.absensi.unggah');

    // 2. Rute Simpan Foto
    Route::post('/absensi/simpan-foto', [KaryawanController::class, 'storeFoto'])
         ->name('karyawan.absensi.storeFoto');

    Route::post('/izin/simpan', [KaryawanController::class, 'storeIzin'])->name('karyawan.izin.store');
});


// --- Rute Admin ---
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {

    // 1. Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])
         ->name('admin.dashboard');

    // 2. Validasi Absensi
    // (Menggunakan showValidasiPage yang baru)
    Route::get('/validasi', [AdminController::class, 'showValidasiPage'])
         ->name('admin.validasi.show');

    Route::post('/validasi/simpan', [AdminController::class, 'submitValidasi'])
         ->name('admin.validasi.submit');

    Route::post('/validasi/izin/simpan', [AdminController::class, 'submitValidasiIzin'])
         ->name('admin.validasi.izin.submit');

    // 3. Manajemen Karyawan (CRUD)
    Route::get('/manajemen-karyawan', [AdminController::class, 'showManajemenKaryawan'])
         ->name('admin.karyawan.index');

    Route::post('/manajemen-karyawan/store', [AdminController::class, 'storeKaryawan'])
         ->name('admin.karyawan.store');

    Route::put('/manajemen-karyawan/update/{id}', [AdminController::class, 'updateKaryawan'])
         ->name('admin.karyawan.update');

    Route::delete('/manajemen-karyawan/destroy/{id}', [AdminController::class, 'destroyKaryawan'])
         ->name('admin.karyawan.destroy');

    // 4. Laporan & Ekspor (INI PERBAIKAN UTAMANYA)
    Route::get('/laporan', [AdminController::class, 'showLaporan'])
         ->name('admin.laporan.show'); // <--- Nama ini yang sebelumnya hilang

    Route::post('/laporan/export', [AdminController::class, 'exportLaporan'])
         ->name('admin.laporan.export');

    // 5. Geofencing
    Route::get('/geofencing', [AdminController::class, 'showGeofencing'])
         ->name('admin.geofencing.show');

    Route::post('/geofencing/save', [AdminController::class, 'saveGeofencing'])
         ->name('admin.geofencing.save');

     Route::get('/profil', [ProfileController::class, 'index'])
          ->name('profil.index');

     Route::post('/profil', [ProfileController::class, 'update'])
          ->name('profil.update');
});
