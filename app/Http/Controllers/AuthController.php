<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    // Fungsi untuk menampilkan Halaman Login
    public function showLogin()
    {
        return view('auth.login');
    }

    // TAMBAHKAN FUNGSI BARU INI
    // Ini adalah simulasi login.
    // Daripada mengecek password, kita langsung arahkan ke dashboard.
    public function handleLogin(Request $request)
    {
        // Langsung redirect ke halaman dashboard
        return redirect('/dashboard');
    }
}