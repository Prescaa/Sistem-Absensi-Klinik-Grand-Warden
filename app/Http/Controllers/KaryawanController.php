<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    // Fungsi untuk menampilkan Dashboard Karyawan
    public function dashboard()
    {
        // Dia akan mencari file di: resources/views/karyawan/dashboard.blade.php
        return view('karyawan.dashboard');
    }

    // Fungsi untuk menampilkan Halaman Unggah
    public function unggah()
    {
        return view('karyawan.unggah');
    }

    // Fungsi untuk menampilkan Halaman Riwayat
    public function riwayat()
    {
        return view('karyawan.riwayat');
    }

    // Fungsi untuk menampilkan Halaman Izin
    public function izin()
    {
        return view('karyawan.izin');
    }

    // Fungsi untuk menampilkan Halaman Profil
    public function profil()
    {
        return view('karyawan.profil');
    }
}