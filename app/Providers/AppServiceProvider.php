<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Leave;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        View::composer('layouts.app', function ($view) {
            $notifications = [];
            $unreadCount = 0;

            if (Auth::check() && Auth::user()->role == 'Karyawan') {
                $empId = Auth::user()->employee->emp_id;

                // 1. Absensi Ditolak (HAPUS take(3))
                $rejectedAbsensi = Attendance::where('emp_id', $empId)
                    ->whereHas('validation', function($q) {
                        $q->where('status_validasi_final', 'Invalid')
                          ->orWhere('status_validasi_final', 'Rejected');
                    })
                    ->where('waktu_unggah', '>=', now()->subDays(7)) // Ambil data 7 hari terakhir saja biar relevan
                    ->orderBy('waktu_unggah', 'desc')
                    // ->take(3) <--- INI DIHAPUS
                    ->get();

                foreach($rejectedAbsensi as $absen) {
                    $notifications[] = [
                        'type' => 'absensi',
                        'title' => 'Absensi Ditolak',
                        'message' => 'Absensi ' . ucfirst($absen->type) . ' tanggal ' . \Carbon\Carbon::parse($absen->waktu_unggah)->format('d M') . ' ditolak.',
                        'time' => $absen->waktu_unggah,
                        'url' => route('karyawan.riwayat')
                    ];
                }

                // 2. Izin Diproses (HAPUS take(3))
                $processedLeaves = Leave::where('emp_id', $empId)
                    ->whereIn('status', ['disetujui', 'ditolak'])
                    ->where('updated_at', '>=', now()->subDays(7)) // Ambil data 7 hari terakhir
                    ->orderBy('updated_at', 'desc')
                    // ->take(3) <--- INI DIHAPUS
                    ->get();

                foreach($processedLeaves as $leave) {
                    $statusMsg = $leave->status == 'disetujui' ? 'Disetujui' : 'Ditolak';
                    $notifications[] = [
                        'type' => 'izin',
                        'title' => 'Pengajuan Izin ' . $statusMsg,
                        'message' => 'Izin ' . ucfirst($leave->tipe_izin) . ' Anda telah ' . $statusMsg . '.',
                        'time' => $leave->updated_at,
                        'url' => route('karyawan.izin')
                    ];
                }
                
                // Sortir gabungan notifikasi berdasarkan waktu terbaru
                usort($notifications, function($a, $b) {
                    return $b['time'] <=> $a['time'];
                });

                $unreadCount = count($notifications);
            }

            $view->with('globalNotifications', $notifications);
            $view->with('notifCount', $unreadCount);
        });
    }
}