<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek Login
        if (!auth()->check()) {
            return redirect('login');
        }

        // 2. Ambil role user dari database
        $userRole = auth()->user()->role; 

        // ==========================================================
        //  SOLUSI FINAL: NORMALISASI TEXT
        // ==========================================================
        
        // Ubah role user jadi huruf kecil & hapus spasi (misal: " Admin " -> "admin")
        $userRoleClean = strtolower(trim($userRole));
        
        // Ubah daftar izin route jadi huruf kecil semua
        $rolesClean = array_map('strtolower', $roles);

        // 3. Cek kecocokan (sekarang "Admin" == "admin")
        if (in_array($userRoleClean, $rolesClean)) {
            return $next($request);
        }

        // 4. Jika masih gagal, tampilkan pesan error yang jelas
        return abort(403, "Akses Ditolak! Role Anda di database ($userRole) tidak cocok dengan izin halaman ini.");
    }
}