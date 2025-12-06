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
        // =====================================================================
        // 1. NOTIFIKASI PORTAL ABSENSI (PERSONAL - layouts.app)
        // =====================================================================
        View::composer('layouts.app', function ($view) {
            $notifications = [];

            // Cek apakah user login DAN terhubung ke data Employee
            if (Auth::check() && Auth::user()->employee) {
                $empId = Auth::user()->employee->emp_id;

                // A. ABSENSI PRIBADI (Valid/Invalid/Rejected) - 5 Hari Terakhir
                // Arahkan ke Riwayat Absensi
                $processedAbsensi = Attendance::where('emp_id', $empId)
                    ->whereHas('validation', function($q) {
                        $q->whereIn('status_validasi_final', ['Valid', 'Invalid', 'Rejected']);
                    })
                    ->where('waktu_unggah', '>=', now()->subDays(5)) // Batasi 5 hari terakhir agar tidak numpuk
                    ->orderBy('waktu_unggah', 'desc')
                    ->get();

                foreach($processedAbsensi as $absen) {
                    $status = $absen->validation->status_validasi_final;
                    $msgStatus = $status == 'Valid' ? 'Disetujui' : 'Ditolak/Invalid';

                    $notifications[] = [
                        'id' => 'absensi_' . $absen->att_id,
                        'type' => 'absensi',
                        'title' => 'Absensi ' . ucfirst($absen->type) . ' ' . $msgStatus,
                        'message' => 'Absensi Anda pada ' . $absen->waktu_unggah->format('d M H:i') . ' telah ' . strtolower($msgStatus) . '.',
                        'time' => $absen->validation->timestamp_validasi ?? $absen->updated_at,
                        'url' => route('karyawan.riwayat') // ARAH KE RIWAYAT ABSENSI
                    ];
                }

                // B. IZIN PRIBADI (Disetujui/Ditolak) - 5 Hari Terakhir
                // Arahkan ke Pengajuan Izin
                $processedLeaves = Leave::where('emp_id', $empId)
                    ->whereIn('status', ['disetujui', 'ditolak'])
                    ->where('updated_at', '>=', now()->subDays(5))
                    ->orderBy('updated_at', 'desc')
                    ->get();

                foreach($processedLeaves as $leave) {
                    $statusMsg = $leave->status == 'disetujui' ? 'Disetujui' : 'Ditolak';
                    $notifications[] = [
                        'id' => 'izin_' . $leave->leave_id,
                        'type' => 'izin',
                        'title' => 'Pengajuan Izin ' . $statusMsg,
                        'message' => 'Izin ' . ucfirst($leave->tipe_izin) . ' Anda telah ' . strtolower($statusMsg) . '.',
                        'time' => $leave->updated_at,
                        'url' => route('karyawan.izin') // ARAH KE PENGAJUAN IZIN
                    ];
                }

                // Urutkan notifikasi dari yang paling baru
                usort($notifications, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
            }
            $view->with('globalNotifications', $notifications);
        });

        // =====================================================================
        // 2. NOTIFIKASI ADMIN (TUGAS ADMIN - layouts.admin_app)
        // =====================================================================
        View::composer('layouts.admin_app', function ($view) {
            $notifications = [];
            if (Auth::check() && strtolower(Auth::user()->role) == 'admin') {

                // A. Notifikasi Absensi (Sudah Divalidasi)
                $processedAbsensi = Attendance::whereHas('validation')
                    ->with(['employee', 'validation'])
                    ->where('waktu_unggah', '>=', now()->subDays(3))
                    ->orderBy('waktu_unggah', 'desc')->get();

                foreach($processedAbsensi as $absen) {
                    $nama = $absen->employee->nama ?? 'Karyawan';
                    $status = $absen->validation->status_validasi_final;
                    $notifications[] = [
                        'id'      => 'adm_hist_' . $absen->att_id,
                        'type'    => 'absensi',
                        'title'   => ($status == 'Valid' ? "Absensi Disetujui" : "Absensi Ditolak"),
                        'message' => "Absen " . ucfirst($absen->type) . " $nama telah $status.",
                        'time'    => $absen->validation->timestamp_validasi ?? $absen->waktu_unggah,
                        'url'     => route('admin.absensi.index'), // Arah ke Manajemen Absensi
                    ];
                }

                // B. Notifikasi Izin (Sudah Diproses)
                $processedIzin = Leave::whereIn('status', ['disetujui', 'ditolak'])
                    ->with('employee')
                    ->where('updated_at', '>=', now()->subDays(3))
                    ->orderBy('updated_at', 'desc')->get();

                foreach($processedIzin as $izin) {
                    $nama = $izin->employee->nama ?? 'Karyawan';
                    $status = ucfirst($izin->status);
                    $notifications[] = [
                        'id'      => 'adm_izin_hist_' . $izin->leave_id,
                        'type'    => 'izin',
                        'title'   => "Izin $status",
                        'message' => "Pengajuan " . ucfirst($izin->tipe_izin) . " $nama telah $status.",
                        'time'    => $izin->updated_at,
                        'url'     => route('admin.izin.index'), // PERBAIKAN: Arah ke Manajemen Izin
                    ];
                }
                usort($notifications, function($a, $b) { return strtotime($b['time']) - strtotime($a['time']); });
            }
            $view->with('adminNotifList', $notifications);
        });

        // =====================================================================
        // 3. NOTIFIKASI MANAJEMEN (TUGAS MANAJER - layouts.manajemen_app)
        // =====================================================================
        View::composer('layouts.manajemen_app', function ($view) {
            $notifications = [];

            if (Auth::check() && strtolower(Auth::user()->role) == 'manajemen') {

                $pendingAbsensi = Attendance::with('employee')
                    ->where(function($query) {
                        $query->whereDoesntHave('validation')
                              ->orWhereHas('validation', function($q) {
                                  $q->where('status_validasi_final', 'Pending');
                              });
                    })
                    ->orderBy('waktu_unggah', 'desc')
                    ->get();

                    
                foreach($pendingAbsensi as $absen) {
                    $nama = $absen->employee->nama ?? 'Karyawan';
                    $isOwn = (Auth::user()->employee->emp_id ?? 0) == $absen->emp_id;
                    $prefix = $isOwn ? "(Anda) " : "";

                    $notifications[] = [
                        'id'      => 'mgmt_att_' . $absen->att_id,
                        'type'    => 'absensi',
                        'title'   => 'Validasi Absensi Baru',
                        'message' => $prefix . "$nama melakukan absen " . ucfirst($absen->type) . ".",
                        'time'    => $absen->waktu_unggah,
                        'url'     => route('manajemen.validasi.show') . '#pills-absensi'
                    ];
                }

                $pendingIzin = Leave::where('status', 'pending')
                    ->with('employee')
                    ->orderBy('created_at', 'desc')
                    ->get();

                foreach($pendingIzin as $izin) {
                    $nama = $izin->employee->nama ?? 'Karyawan';
                    $tipe = ucfirst($izin->tipe_izin);
                    $isOwn = (Auth::user()->employee->emp_id ?? 0) == $izin->emp_id;
                    $prefix = $isOwn ? "(Anda) " : "";

                    $notifications[] = [
                        'id'      => 'mgmt_leave_' . $izin->leave_id,
                        'type'    => 'izin',
                        'title'   => 'Pengajuan Izin Baru',
                        'message' => $prefix . "$nama mengajukan $tipe.",
                        'time'    => $izin->created_at,
                        'url'     => route('manajemen.validasi.show') . '#pills-izin'
                    ];
                }

                usort($notifications, function($a, $b) {
                    return strtotime($b['time']) - strtotime($a['time']);
                });
            }

            $view->with('manajemenNotifList', $notifications);
        });
    }
}
