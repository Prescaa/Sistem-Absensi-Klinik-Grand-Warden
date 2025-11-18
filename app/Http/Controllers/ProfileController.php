<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        // Ambil user yang sedang login beserta data karyawannya
        $user = Auth::user();
        $employee = $user->employee;

        return view('karyawan.profil', compact('user', 'employee'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'alamat' => 'nullable|string|max:500',
            'no_telepon' => 'nullable|string|max:20',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        $user = Auth::user();
        $employee = $user->employee;

        // 1. Update Data Teks
        $employee->alamat = $request->alamat;
        $employee->no_telepon = $request->no_telepon;

        // 2. Handle Upload Foto
        if ($request->hasFile('foto_profil')) {
            // Hapus foto lama jika ada agar hemat storage
            if ($employee->foto_profil && Storage::exists('public/' . $employee->foto_profil)) {
                Storage::delete('public/' . $employee->foto_profil);
            }

            // Simpan foto baru
            $path = $request->file('foto_profil')->store('fotos', 'public');
            $employee->foto_profil = $path;
        }

        $employee->save();

        return redirect()->back()->with('success', 'Profil berhasil diperbarui.');
    }
}