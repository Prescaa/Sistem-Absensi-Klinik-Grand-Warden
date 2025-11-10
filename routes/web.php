<?php

use Illuminate\Support\Facades\Route;

// 1. "Import" Controller yang sudah kita buat
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rute untuk Halaman Login
// URL: /login -> memanggil fungsi showLogin di AuthController
Route::get('/login', [AuthController::class, 'showLogin']);


// Rute untuk Halaman Karyawan
// URL: /dashboard -> memanggil fungsi dashboard di KaryawanController
Route::get('/dashboard', [KaryawanController::class, 'dashboard']);

// URL: /unggah
Route::get('/unggah', [KaryawanController::class, 'unggah']);

// URL: /riwayat
Route::get('/riwayat', [KaryawanController::class, 'riwayat']);

// URL: /izin
Route::get('/izin', [KaryawanController::class, 'izin']);

// URL: /profil
Route::get('/profil', [KaryawanController::class, 'profil']);


// Rute Halaman Awal
// Jika ada yang buka '/', arahkan otomatis ke halaman /login
Route::get('/', function () {
    return redirect('/login');
});