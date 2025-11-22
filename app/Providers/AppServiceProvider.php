<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Validation;
use App\Models\Employee;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // ==============================
        // NOTIFIKASI KARYAWAN
        // ==============================
        View::composer('layouts.app', function ($view) {
            $notifications = [];
            
            // Hapus logika cookie di sini, biarkan JS yang handle visual badgenya
            // Kita kirim semua notifikasi 7 hari terakhir agar dropdown tidak kosong

            if (Auth::check() && strtolower(Auth::user()->role) == 'karyawan') {
                $empId = Auth::user()->employee->emp_id ?? null;

                if ($empId) {
                    // 1. Notifikasi Absensi (Ditolak)
                    $rejectedAbsensi = Attendance::where('emp_id', $empId)
                        ->whereHas('validation', function($q) {
                            $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
                        })
                        ->where('waktu_unggah', '>=', now()->subDays(7))
                        ->orderBy('waktu_unggah', 'desc')
                        ->get();

                    foreach($rejectedAbsensi as $absen) {
                        $notificationId = 'absensi_' . $absen->att_id;
                        
                        $notifications[] = [
                            'id' => $notificationId,
                            'type' => 'absensi',
                            'title' => 'Absensi Ditolak',
                            'message' => 'Absensi ' . ucfirst($absen->type) . ' tanggal ' .
                                \Carbon\Carbon::parse($absen->waktu_unggah)->format('d M') . ' ditolak.',
                            'time' => $absen->waktu_unggah,
                            'url' => route('karyawan.riwayat') . '#absensi' 
                        ];
                    }

                    // 2. Notifikasi Izin/Sakit/Cuti (Disetujui/Ditolak)
                    $processedLeaves = Leave::where('emp_id', $empId)
                        ->whereIn('status', ['disetujui', 'ditolak'])
                        ->where('updated_at', '>=', now()->subDays(7))
                        ->orderBy('updated_at', 'desc')
                        ->get();

                    foreach($processedLeaves as $leave) {
                        $notificationId = 'izin_' . $leave->leave_id;
                        
                        $statusMsg = $leave->status == 'disetujui' ? 'Disetujui' : 'Ditolak';
                        $notifications[] = [
                            'id' => $notificationId,
                            'type' => 'izin',
                            'title' => 'Pengajuan Izin ' . $statusMsg,
                            'message' => 'Izin ' . ucfirst($leave->tipe_izin) . ' Anda telah ' . $statusMsg . '.',
                            'time' => $leave->updated_at,
                            'url' => route('karyawan.riwayat') . '#izin' 
                        ];
                    }

                    usort($notifications, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
                }
            }

            // Kirim jumlah TOTAL, nanti JS yang kurangi dengan yang sudah dilihat
            $view->with('globalNotifications', $notifications);
        });

        // ==============================
        // NOTIFIKASI ADMIN
        // ==============================
        View::composer('layouts.admin_app', function ($view) {
            $notifications = [];
            
            if (Auth::check() && strtolower(Auth::user()->role) == 'admin') {
                
                // 1. ABSENSI BARU 
                $pendingAbsensi = Attendance::whereDoesntHave('validation')
                    ->orWhereHas('validation', function($q) {
                        $q->where('status_validasi_final', 'Pending');
                    })
                    ->with('employee')
                    ->orderBy('waktu_unggah', 'desc')
                    ->get();

                foreach($pendingAbsensi as $absen) {
                    $nama = $absen->employee->nama ?? 'Karyawan';
                    $tipe = ucfirst($absen->type); 
                    
                    // ID Unik untuk cookie
                    $notificationId = 'adm_abs_' . $absen->att_id;

                    $notifications[] = [
                        'id'      => $notificationId,
                        'type'    => 'absensi',
                        'title'   => 'Validasi Absensi Baru',
                        'message' => "$nama melakukan absen $tipe",
                        'time'    => $absen->waktu_unggah, 
                        'url'     => url('/admin/validasi#absensi'), 
                    ];
                }

                // 2. IZIN BARU
                $pendingIzin = Leave::where('status', 'pending')
                    ->with('employee')
                    ->orderBy('created_at', 'desc')
                    ->get();

                foreach($pendingIzin as $izin) {
                    $nama = $izin->employee->nama ?? 'Karyawan';
                    $tipe = ucfirst($izin->tipe_izin);

                    // ID Unik untuk cookie
                    $notificationId = 'adm_izin_' . $izin->leave_id;

                    $notifications[] = [
                        'id'      => $notificationId,
                        'type'    => 'izin',
                        'title'   => "Pengajuan $tipe",
                        'message' => "$nama mengajukan $tipe",
                        'time'    => $izin->created_at, 
                        'url'     => url('/admin/validasi#izin'),
                    ];
                }

                usort($notifications, function($a, $b) {
                    return strtotime($b['time']) - strtotime($a['time']);
                });
            }

            $view->with('adminNotifList', $notifications);
        });
    }
}