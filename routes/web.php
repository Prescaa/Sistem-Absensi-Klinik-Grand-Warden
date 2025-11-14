<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;

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