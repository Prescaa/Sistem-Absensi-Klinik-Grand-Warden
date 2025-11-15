<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role  // Ini adalah parameter (e.g., 'admin' atau 'karyawan')
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        // 1. Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = $request->user();

        // --- PERUBAHAN DI SINI ---
        // Kita ubah kedua string menjadi huruf kecil sebelum membandingkan
        // strtolower($user->role) akan menjadi "admin"
        // strtolower($role) akan menjadi "admin"
        // "admin" === "admin" (TRUE)
        if (strtolower($user->role) === strtolower($role)) {
            // Jika Sesuai (admin ke /admin, karyawan ke /karyawan), lanjutkan
            return $next($request);
        }

        // 3. --- LOGIKA INI JUGA DIPERBAIKI ---
        // Jika user adalah "Admin" (diubah ke "admin"), tapi mencoba akses halaman karyawan
        if (strtolower($user->role) === 'admin') {
            return redirect('/admin/dashboard')->with('error', 'Anda tidak bisa mengakses halaman karyawan.');
        }

        // Jika user adalah "Karyawan" (diubah ke "karyawan"), tapi mencoba akses halaman admin
        if (strtolower($user->role) === 'karyawan') {
            return redirect('/dashboard')->with('error', 'Anda tidak memiliki akses admin.');
        }

        // 4. Fallback jika user tidak punya role sama sekali
        Auth::logout();
        return redirect('/login')->with('error', 'Role akun Anda tidak terdaftar.');
    }
}
