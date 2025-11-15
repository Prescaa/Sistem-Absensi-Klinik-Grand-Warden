<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'handleLogin']);

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [KaryawanController::class, 'dashboard']);
    Route::get('/unggah', [KaryawanController::class, 'unggah']);
    Route::get('/riwayat', [KaryawanController::class, 'riwayat']);
    Route::get('/izin', [KaryawanController::class, 'izin']);
    Route::get('/profil', [KaryawanController::class, 'profil']);
    Route::get('/logout', [AuthController::class, 'logout']);

});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {

    // Route for /admin/dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    Route::get('/validasi', [AdminController::class, 'showValidasi']);
    Route::post('/validasi/approve', [AdminController::class, 'handleApprove']);
    Route::post('/validasi/reject', [AdminController::class, 'handleReject']);

    Route::get('/manajemen-karyawan', [AdminController::class, 'showManajemenKaryawan']);
    Route::post('/manajemen-karyawan/store', [AdminController::class, 'storeKaryawan']);
    // ... other routes for edit/delete ...

    Route::get('/laporan', [AdminController::class, 'showLaporan']);
    Route::post('/laporan/export', [AdminController::class, 'exportLaporan']);

});
