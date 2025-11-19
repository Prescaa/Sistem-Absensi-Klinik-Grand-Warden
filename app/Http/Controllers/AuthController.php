<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

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
        // 1. Define a unique throttle key based on the user's IP
        // You can also append the username to throttle by account: $request->input('username') . '|' . $request->ip()
        $throttleKey = 'login-attempt:' . $request->ip();

        // 2. Check if the user has tried too many times (e.g., 5 times)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', "Terlalu banyak percobaan login. Silakan coba lagi dalam $seconds detik.");
        }

        // 3. Validate inputs
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // 4. Attempt login
        if (Auth::attempt($credentials)) {
            // SUCCESS: Clear the rate limiter so they aren't blocked next time
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();

            // ... existing role check logic ...
            $user = Auth::user();
            if (strtolower($user->role) === 'admin') {
                return redirect()->intended('/admin/dashboard');
            }
            if (strtolower($user->role) === 'karyawan') {
                return redirect()->intended('/dashboard');
            }

            Auth::logout();
            return back()->with('error', 'Akun Anda tidak memiliki role yang valid.');
        }

        // FAILURE: Increment the attempt counter
        // The second argument '60' means the "hit" stays in memory for 60 seconds
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
