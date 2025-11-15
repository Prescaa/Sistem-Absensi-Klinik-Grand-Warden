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
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    public function handleLogin(Request $request)
    {
        // 1. VALIDATE THE ROLE along with username and password
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
            'role'     => 'required|string', // This line is new
        ]);

        // 2. ATTEMPT LOGIN using all three credentials (username, password, AND role)
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        // 3. Update the error message
        return back()->with('error', 'Username, Password, atau Role salah!');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
