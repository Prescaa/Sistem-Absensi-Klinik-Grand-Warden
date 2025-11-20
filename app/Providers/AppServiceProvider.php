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
        /**
         * ==============================
         * NOTIFIKASI KARYAWAN
         * ==============================
         */
        View::composer('layouts.app', function ($view) {
            $notifications = [];
            $unreadCount = 0;

            if (Auth::check() && Auth::user()->role == 'Karyawan') {
                $empId = Auth::user()->employee->emp_id;

                // Absensi ditolak
                $rejectedAbsensi = Attendance::where('emp_id', $empId)
                    ->whereHas('validation', function($q) {
                        $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
                    })
                    ->where('waktu_unggah', '>=', now()->subDays(7))
                    ->orderBy('waktu_unggah', 'desc')
                    ->get();

                foreach($rejectedAbsensi as $absen) {
                    $notifications[] = [
                        'type' => 'absensi',
                        'title' => 'Absensi Ditolak',
                        'message' => 'Absensi ' . ucfirst($absen->type) . ' tanggal ' .
                            \Carbon\Carbon::parse($absen->waktu_unggah)->format('d M') . ' ditolak.',
                        'time' => $absen->waktu_unggah,
                        'url' => url('/karyawan/riwayat')
                    ];
                }

                // Izin disetujui / ditolak
                $processedLeaves = Leave::where('emp_id', $empId)
                    ->whereIn('status', ['disetujui', 'ditolak'])
                    ->where('updated_at', '>=', now()->subDays(7))
                    ->orderBy('updated_at', 'desc')
                    ->get();

                foreach($processedLeaves as $leave) {
                    $statusMsg = $leave->status == 'disetujui' ? 'Disetujui' : 'Ditolak';
                    $notifications[] = [
                        'type' => 'izin',
                        'title' => 'Pengajuan Izin ' . $statusMsg,
                        'message' => 'Izin ' . ucfirst($leave->tipe_izin) . ' Anda telah ' . $statusMsg . '.',
                        'time' => $leave->updated_at,
                        'url' => url('/karyawan/izin')
                    ];
                }

                usort($notifications, fn($a, $b) => $b['time'] <=> $a['time']);
                $unreadCount = count($notifications);
            }

            $view->with('globalNotifications', $notifications);
            $view->with('notifCount', $unreadCount);
        });


        /**
         * ==============================
         * NOTIFIKASI ADMIN (DIPERBAIKI)
         * ==============================
         */
        View::composer('layouts.admin_app', function ($view) {
            $notifications = [];
            
            if (Auth::check() && Auth::user()->role == 'Admin') {
                
                // 1. Ambil Absensi Baru (Belum divalidasi SAMA SEKALI atau status Pending)
                // PERBAIKAN: Menggunakan whereDoesntHave untuk data baru
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
                    
                    $notifications[] = [
                        'type'    => 'absensi',
                        'title'   => 'Validasi Absensi Baru',
                        'message' => "$nama melakukan absen $tipe",
                        'time'    => $absen->waktu_unggah,
                        'url'     => url('/admin/validasi#absensi'),
                        'is_new'  => true
                    ];
                }

                // 2. Ambil Pengajuan Izin Baru (Pending)
                $pendingIzin = Leave::where('status', 'pending')
                    ->with('employee')
                    ->orderBy('created_at', 'desc')
                    ->get();

                foreach($pendingIzin as $izin) {
                    $nama = $izin->employee->nama ?? 'Karyawan';
                    $tipe = ucfirst($izin->tipe_izin);

                    $notifications[] = [
                        'type'    => 'izin',
                        'title'   => "Pengajuan $tipe",
                        'message' => "$nama mengajukan $tipe",
                        'time'    => $izin->created_at,
                        'url'     => url('/admin/validasi#izin'),
                        'is_new'  => true
                    ];
                }

                // 3. Urutkan dari yang terbaru
                usort($notifications, function($a, $b) {
                    return strtotime($b['time']) - strtotime($a['time']);
                });
            }

            $view->with('adminNotifList', $notifications);
            $view->with('adminNotifCount', count($notifications));
        });
    }
}