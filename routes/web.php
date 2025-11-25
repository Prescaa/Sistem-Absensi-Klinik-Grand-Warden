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

    // ❌ Rute Validasi DIHAPUS dari sini (Dipindah ke Manajemen)

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

    // Profil Admin
    Route::get('/profil', [ProfileController::class, 'index'])->name('admin.profil');
    Route::post('/profil', [ProfileController::class, 'update'])->name('admin.profil.update');
    Route::delete('/profil/hapus-foto', [ProfileController::class, 'deleteFotoAdmin'])->name('admin.profil.deleteFoto');

    Route::get('/manajemen-absensi', [AdminController::class, 'showManajemenAbsensi'])->name('admin.absensi.index');
    Route::post('/manajemen-absensi/store', [AdminController::class, 'storeAbsensi'])->name('admin.absensi.store');
    Route::put('/manajemen-absensi/update/{id}', [AdminController::class, 'updateAbsensi'])->name('admin.absensi.update');
    Route::delete('/manajemen-absensi/destroy/{id}', [AdminController::class, 'destroyAbsensi'])->name('admin.absensi.destroy');

    // --- MANAJEMEN IZIN (CRUD) ---
    Route::get('/manajemen-izin', [AdminController::class, 'showManajemenIzin'])->name('admin.izin.index');
    Route::post('/manajemen-izin/store', [AdminController::class, 'storeIzin'])->name('admin.izin.store');
    Route::put('/manajemen-izin/update/{id}', [AdminController::class, 'updateIzin'])->name('admin.izin.update');
    Route::delete('/manajemen-izin/destroy/{id}', [AdminController::class, 'destroyIzin'])->name('admin.izin.destroy');

    // TAMBAHKAN INI:
    Route::get('/absensi/unggah', [AdminController::class, 'showUnggah'])->name('admin.absensi.unggah');
    Route::post('/absensi/simpan', [AdminController::class, 'storeFoto'])->name('admin.absensi.storeFoto');
    Route::get('/absensi/riwayat', [AdminController::class, 'showRiwayat'])->name('admin.absensi.riwayat');
    Route::get('/izin', [AdminController::class, 'showIzin'])->name('admin.izin.show');
});

// --- GROUP MANAJEMEN ---
Route::middleware(['auth', 'role:manajemen'])->prefix('manajemen')->group(function () {
    Route::get('/dashboard', [ManajemenController::class, 'dashboard'])->name('manajemen.dashboard');

    // ✅ FITUR VALIDASI (Dipindahkan ke sini)
    Route::get('/validasi', [ManajemenController::class, 'showValidasiPage'])->name('manajemen.validasi.show');
    Route::post('/validasi/simpan', [ManajemenController::class, 'submitValidasi'])->name('manajemen.validasi.submit');
    Route::post('/validasi/izin/simpan', [ManajemenController::class, 'submitValidasiIzin'])->name('manajemen.validasi.izin.submit');

    // Halaman Laporan Detail (Tabel)
    Route::get('/laporan', [ManajemenController::class, 'showLaporanPage'])->name('manajemen.laporan.index');

    // Export CSV
    Route::post('/laporan/export', [ManajemenController::class, 'exportLaporan'])->name('manajemen.laporan.export');

    // Profil Manajemen
    Route::get('/profil', [ProfileController::class, 'index'])->name('manajemen.profil');
    Route::post('/profil', [ProfileController::class, 'update'])->name('manajemen.profil.update');
    Route::delete('/profil/hapus-foto', [ProfileController::class, 'deleteFotoAdmin'])->name('manajemen.profil.deleteFoto');

    // TAMBAHKAN INI:
    Route::get('/absensi/unggah', [ManajemenController::class, 'showUnggah'])->name('manajemen.absensi.unggah');
    Route::post('/absensi/simpan', [ManajemenController::class, 'storeFoto'])->name('manajemen.absensi.storeFoto');
    
    Route::get('/absensi/riwayat', [ManajemenController::class, 'showRiwayat'])->name('manajemen.absensi.riwayat');
    
    Route::get('/izin', [ManajemenController::class, 'showIzin'])->name('manajemen.izin.show');
    Route::post('/izin/simpan', [ManajemenController::class, 'storeIzin'])->name('manajemen.izin.store');
});


// ... (kode route login/logout yang sudah ada) ...

// ====================================================
//  ROUTE ABSENSI UNIVERSAL (Admin, Manajemen, Karyawan)
// ====================================================
// Route::middleware(['auth', 'role:admin,manajemen,karyawan'])->group(function () {
    
//     // Halaman Absen (Form Upload)
//     Route::get('/absensi/buat', [AbsensiController::class, 'create'])
//         ->name('absensi.create');

//     // Proses Simpan Absen
//     Route::post('/absensi/simpan', [AbsensiController::class, 'store'])
//         ->name('absensi.store');

//     // Halaman Riwayat Absensi
//     Route::get('/absensi/riwayat', [AbsensiController::class, 'riwayat'])
//         ->name('absensi.riwayat');
        
//     // Cek EXIF (Ajax)
//     Route::post('/absensi/check-exif', [AbsensiController::class, 'checkExif'])
//         ->name('absensi.check-exif');
// });

// Catatan: Jika ada route lama 'karyawan/unggah' yang konflik, 
// sebaiknya dikomentari (disable) agar tidak bingung.

Route::get('/debug-python', function () {
    // 1. Cek apakah fungsi shell_exec aktif
    if (!function_exists('shell_exec')) {
        return "ERROR: Fungsi shell_exec dimatikan di php.ini. Harap hapus shell_exec dari disable_functions.";
    }

    // 2. Cek Versi Python (Apakah perintah 'python' dikenali?)
    $version = shell_exec("python --version 2>&1");
    if (empty($version)) {
        return "ERROR: Perintah 'python' tidak dikenali. Coba gunakan path lengkap (misal: C:\\Users\\acer\\...\\python.exe) atau tambahkan ke Environment Variables Windows.";
    }

    // 3. Cek Script Deteksi Wajah
    $scriptPath = base_path('app/Python/detect_face.py');
    if (!file_exists($scriptPath)) {
        return "ERROR: File script tidak ditemukan di: $scriptPath";
    }

    // 4. Simulasi Jalankan Script (Tanpa Gambar)
    // Script kita harusnya print "error" jika tanpa argumen, bukan crash/blank.
    $output = shell_exec("python " . escapeshellarg($scriptPath) . " 2>&1");

    return "<h1>Status Diagnosa:</h1>" .
           "<p><b>Python Version:</b> <pre>$version</pre></p>" .
           "<p><b>Script Path:</b> $scriptPath</p>" .
           "<p><b>Output Script (Test Run):</b> <pre>$output</pre></p>";
});
