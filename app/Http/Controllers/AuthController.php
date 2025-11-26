<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter; // Pastikan ini ada
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            $role = strtolower(Auth::user()->role);

            if ($role === 'admin') {
                return redirect('/admin/dashboard');
            }
            if ($role === 'manajemen') {
                return redirect('/manajemen/dashboard');
            }

            return redirect('/dashboard');
        }

        // 2. --- LOGIKA BARU (PERSISTENCE) ---
        // Cek apakah IP ini sedang dalam masa tunggu (Rate Limited)
        // Kita lakukan ini di GET request agar saat di-refresh tetap terdeteksi
        $throttleKey = 'login-attempt:' . $request->ip();
        $secondsRemaining = 0;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $secondsRemaining = RateLimiter::availableIn($throttleKey);
        }

        // Kirim variabel $secondsRemaining ke View
        return view('auth.login', compact('secondsRemaining'));
    }

    public function handleLogin(Request $request)
    {
        $throttleKey = 'login-attempt:' . $request->ip();

        // 3. Cek Rate Limiter
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            // Jika kena limit, langsung kembalikan saja.
            // Fungsi showLogin() di atas yang akan menangani tampilan error & countdown-nya.
            return back()->withInput();
        }

        // 4. Validasi & Login (Logika lama)
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();
            if (strtolower($user->role) === 'admin') {
                return redirect()->intended('/admin/dashboard');
            }
            if (strtolower($user->role) === 'karyawan') {
                return redirect()->intended('/dashboard');
            }
            if (strtolower($user->role) === 'manajemen') {
                return redirect()->intended('/manajemen/dashboard');
            }

            Auth::logout();
            return back()->with('error', 'Akun Anda tidak memiliki role yang valid.');
        }

        // 5. Jika Gagal Login
        RateLimiter::hit($throttleKey, 60);

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
