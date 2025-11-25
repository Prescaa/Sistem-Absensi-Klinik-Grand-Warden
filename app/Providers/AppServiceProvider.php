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

                // 1. RIWAYAT ABSENSI (YANG SUDAH DIVALIDASI MANAJEMEN)
                $processedAbsensi = Attendance::whereHas('validation')
                    ->with(['employee', 'validation'])
                    ->where('waktu_unggah', '>=', now()->subDays(3))
                    ->orderBy('waktu_unggah', 'desc') // ✅ PERBAIKAN: Ganti updated_at -> waktu_unggah
                    ->get();

                foreach($processedAbsensi as $absen) {
                    $nama = $absen->employee->nama ?? 'Karyawan';
                    $tipe = ucfirst($absen->type);
                    $status = $absen->validation->status_validasi_final;

                    if ($status == 'Valid') {
                        $title = "Absensi Disetujui";
                        $msg   = "Absen $tipe $nama telah disetujui.";
                    } else {
                        $title = "Absensi Ditolak";
                        $msg   = "Absen $tipe $nama dinyatakan Invalid.";
                    }

                    $notifications[] = [
                        'id'      => 'adm_hist_' . $absen->att_id,
                        'type'    => 'absensi',
                        'title'   => $title,
                        'message' => $msg,
                        // ✅ PERBAIKAN: Fallback ke waktu_unggah jika timestamp validasi null
                        'time'    => $absen->validation->timestamp_validasi ?? $absen->waktu_unggah,
                        'url'     => route('admin.absensi.index'),
                    ];
                }

                // 2. RIWAYAT IZIN
                $processedIzin = Leave::whereIn('status', ['disetujui', 'ditolak'])
                    ->with('employee')
                    ->where('updated_at', '>=', now()->subDays(3))
                    ->orderBy('updated_at', 'desc') // ✅ Aman: Leave punya timestamps
                    ->get();

                foreach($processedIzin as $izin) {
                    $nama = $izin->employee->nama ?? 'Karyawan';
                    $tipe = ucfirst($izin->tipe_izin);
                    $status = ucfirst($izin->status);

                    $notifications[] = [
                        'id'      => 'adm_izin_hist_' . $izin->leave_id,
                        'type'    => 'izin',
                        'title'   => "Izin $status",
                        'message' => "Pengajuan $tipe $nama telah $status.",
                        'time'    => $izin->updated_at,
                        'url'     => route('admin.absensi.index'),
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
