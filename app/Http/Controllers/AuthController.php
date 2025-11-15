<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            // --- PERUBAHAN DI SINI ---
            // Menggunakan strtolower() untuk pengecekan
            if (strtolower(Auth::user()->role) === 'admin') {
                return redirect('/admin/dashboard');
            }
            // Asumsikan selain itu adalah karyawan
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    public function handleLogin(Request $request)
    {
        // 1. Validate only username and password
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // 2. Attempt login with just username and password
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // 3. --- PERUBAHAN DI SINI ---
            // Cek role setelah login berhasil
            $user = Auth::user();

            // Menggunakan strtolower() untuk pengecekan
            if (strtolower($user->role) === 'admin') {
                return redirect()->intended('/admin/dashboard');
            }

            if (strtolower($user->role) === 'karyawan') {
                return redirect()->intended('/dashboard');
            }

            // Fallback jika user tidak punya role
            Auth::logout();
            return back()->with('error', 'Akun Anda tidak memiliki role yang valid.');
        }

        // 4. Reverted error message
        return back()->with('error', 'Username atau Password salah!');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
