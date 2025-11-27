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


// --- RUTE PORTAL KARYAWAN (ABSENSI & PERSONAL) ---
// Perubahan: Middleware ditambahkan 'admin' dan 'manajemen' agar mereka bisa akses
Route::middleware(['auth', 'role:karyawan,admin,manajemen'])->group(function () {

    Route::get('/dashboard', [KaryawanController::class, 'dashboard'])->name('karyawan.dashboard');
    Route::get('/unggah', [KaryawanController::class, 'unggah'])->name('karyawan.unggah');
    Route::get('/riwayat', [KaryawanController::class, 'riwayat'])->name('karyawan.riwayat');
    Route::get('/izin', [KaryawanController::class, 'izin'])->name('karyawan.izin');
    Route::get('/profil', [KaryawanController::class, 'profil'])->name('karyawan.profil');

    // Rute Update & Hapus Foto Profil (Personal)
    Route::put('/profil/update', [KaryawanController::class, 'updateProfil'])->name('karyawan.profil.update');
    Route::delete('/profil/hapus-foto', [KaryawanController::class, 'deleteFotoProfil'])->name('karyawan.profil.deleteFoto');

    // Rute Absensi Personal
    Route::get('/absensi/unggah/{type}', [KaryawanController::class, 'showUploadForm'])->name('karyawan.absensi.unggah');
    Route::post('/karyawan/absensi/check-exif', [KaryawanController::class, 'checkExif'])->name('karyawan.absensi.checkExif');
    Route::post('/absensi/simpan-foto', [KaryawanController::class, 'storeFoto'])->middleware('throttle:5,1')->name('karyawan.absensi.storeFoto');

    // Rute Izin Personal
    Route::post('/izin/simpan', [KaryawanController::class, 'storeIzin'])->name('karyawan.izin.store');
});


// --- GROUP ADMIN (Fokus Operasional & Manajemen Data) ---
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

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

    // Profil Admin (Akun User)
    Route::get('/profil', [ProfileController::class, 'index'])->name('admin.profil');
    Route::post('/profil', [ProfileController::class, 'update'])->name('admin.profil.update');
    Route::delete('/profil/hapus-foto', [ProfileController::class, 'deleteFotoAdmin'])->name('admin.profil.deleteFoto');

    // Manajemen Absensi (CRUD Data Orang Lain)
    Route::get('/manajemen-absensi', [AdminController::class, 'showManajemenAbsensi'])->name('admin.absensi.index');
    Route::post('/manajemen-absensi/store', [AdminController::class, 'storeAbsensi'])->name('admin.absensi.store');
    Route::put('/manajemen-absensi/update/{id}', [AdminController::class, 'updateAbsensi'])->name('admin.absensi.update');
    Route::delete('/manajemen-absensi/destroy/{id}', [AdminController::class, 'destroyAbsensi'])->name('admin.absensi.destroy');

    // Manajemen Izin (Approval)
    Route::get('/manajemen-izin', [AdminController::class, 'showManajemenIzin'])->name('admin.izin.index');

    // ✅ PERBAIKAN: Tambahkan route store untuk admin input izin karyawan
    Route::post('/manajemen-izin/store', [AdminController::class, 'storeIzin'])->name('admin.izin.store');

    Route::put('/manajemen-izin/update/{id}', [AdminController::class, 'updateIzin'])->name('admin.izin.update');
    Route::delete('/manajemen-izin/destroy/{id}', [AdminController::class, 'destroyIzin'])->name('admin.izin.destroy');
    // [DIHAPUS] Rute Absensi Personal Admin (Unggah, Riwayat, Izin Pribadi)
    // Admin sekarang menggunakan rute '/unggah', '/riwayat' milik group Karyawan di atas.
});

// --- GROUP MANAJEMEN ---
Route::middleware(['auth', 'role:manajemen'])->prefix('manajemen')->group(function () {
    Route::get('/dashboard', [ManajemenController::class, 'dashboard'])->name('manajemen.dashboard');

    // Fitur Validasi
    Route::get('/validasi', [ManajemenController::class, 'showValidasiPage'])->name('manajemen.validasi.show');
    Route::post('/validasi/simpan', [ManajemenController::class, 'submitValidasi'])->name('manajemen.validasi.submit');
    Route::post('/validasi/izin/simpan', [ManajemenController::class, 'submitValidasiIzin'])->name('manajemen.validasi.izin.submit');

    // Halaman Laporan Detail (Tabel)
    Route::get('/laporan', [ManajemenController::class, 'showLaporanPage'])->name('manajemen.laporan.index');
    Route::post('/laporan/export', [ManajemenController::class, 'exportLaporan'])->name('manajemen.laporan.export');

    // Profil Manajemen
    Route::get('/profil', [ProfileController::class, 'index'])->name('manajemen.profil');
    Route::post('/profil', [ProfileController::class, 'update'])->name('manajemen.profil.update');
    Route::delete('/profil/hapus-foto', [ProfileController::class, 'deleteFotoAdmin'])->name('manajemen.profil.deleteFoto');

    // [DIHAPUS] Rute Absensi Personal Manajemen
    // Manajemen sekarang menggunakan rute '/unggah', '/riwayat' milik group Karyawan.
});