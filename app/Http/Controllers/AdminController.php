<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Models\Validation;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Menampilkan halaman dashboard admin.
     */
    public function dashboard()
    {
        // TODO: Ambil data statistik untuk dashboard
        // $pendingValidations = Validation::where('status_validasi_final', 'pending')->count();
        // $totalEmployees = Employee::count();

        // Anda perlu membuat file ini: resources/views/admin/dashboard.blade.php
        return view('admin.dashboard');
    }

    /**
     * Menampilkan halaman validasi absensi.
     */
    public function showValidasi()
    {
        // TODO: Ambil data absensi yang perlu divalidasi
        // $attendances = Attendance::whereDoesntHave('validation')->with('employee')->get();

        // Anda perlu membuat file ini: resources/views/admin/validasi.blade.php
        return view('admin.validasi');
    }

    /**
     * Menangani proses approve absensi.
     */
    public function handleApprove(Request $request)
    {
        // TODO: Logika untuk approve absensi
        // $validation = Validation::find($request->input('validation_id'));
        // $validation->update(['status_validasi_final' => 'approved']);
        return redirect()->back()->with('success', 'Absensi telah disetujui.');
    }

    /**
     * Menangani proses reject absensi.
     */
    public function handleReject(Request $request)
    {
        // TODO: Logika untuk reject absensi
        // $validation = Validation::find($request->input('validation_id'));
        // $validation->update(['status_validasi_final' => 'rejected', 'catatan_admin' => $request->input('catatan')]);
        return redirect()->back()->with('success', 'Absensi telah ditolak.');
    }

    /**
     * Menampilkan halaman manajemen karyawan.
     */
    public function showManajemenKaryawan()
    {
        // TODO: Ambil semua data karyawan
        // $employees = Employee::with('user')->get();

        // Anda perlu membuat file ini: resources/views/admin/manajemen_karyawan.blade.php
        return view('admin.manajemen_karyawan');
    }

    /**
     * Menangani proses penambahan karyawan baru.
     */
    public function storeKaryawan(Request $request)
    {
        // TODO: Validasi dan logika untuk menambah karyawan baru
        // $request->validate([...]);
        // $user = User::create([...]);
        // $employee = Employee::create([...]);
        return redirect()->back()->with('success', 'Karyawan baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan halaman laporan.
     */
    public function showLaporan()
    {
        // Anda perlu membuat file ini: resources/views/admin/laporan.blade.php
        return view('admin.laporan');
    }

    /**
     * Menangani proses ekspor laporan.
     */
    public function exportLaporan(Request $request)
    {
        // TODO: Logika untuk memfilter data dan mengekspor ke Excel/PDF
        return redirect()->back()->with('success', 'Laporan sedang diproses.');
    }
}
