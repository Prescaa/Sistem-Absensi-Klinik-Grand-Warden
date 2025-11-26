<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\WorkArea; // ✅ Pastikan Model ini di-import
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManajemenController extends Controller
{
    /**
     * Menampilkan Dashboard Manajemen (Statistik & Analisis)
     */
    public function dashboard()
    {
        $today = Carbon::today(); 

        // 1. Statistik Utama
        $totalEmployees = Employee::count();

        $presentCount = Attendance::whereDate('waktu_unggah', $today)
            ->where('type', 'masuk')
            ->distinct('emp_id')
            ->count('emp_id');

        $izinCount = Leave::where('tipe_izin', 'izin')
            ->where('status', 'disetujui')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->count();

        $sakitCount = Leave::where('tipe_izin', 'sakit')
            ->where('status', 'disetujui')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->count();

        // 2. Grafik Kehadiran 7 Hari Terakhir (Analisis Tren)
        $labels = [];
        $dataHadir = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d M');
            
            $count = Attendance::whereDate('waktu_unggah', $date)
                ->where('type', 'masuk')
                ->distinct('emp_id')
                ->count('emp_id');
            $dataHadir[] = $count;
        }

        return view('manajemen.dashboard', [
            'totalEmployees' => $totalEmployees,
            'presentCount' => $presentCount,
            'izinCount' => $izinCount,
            'sakitCount' => $sakitCount,
            'chartLabels' => $labels,
            'chartData' => $dataHadir
        ]);
    }

    /**
     * Menampilkan Halaman Laporan
     */
    public function showLaporanPage(Request $request)
    {
        $query = Attendance::with(['employee', 'validation'])
            ->orderBy('waktu_unggah', 'desc');

        // Filter Tanggal jika ada input
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('waktu_unggah', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        } else {
            // Default: Tampilkan data bulan ini
            $query->whereMonth('waktu_unggah', Carbon::now()->month);
        }

        $attendances = $query->get(); 

        return view('manajemen.laporan', compact('attendances'));
    }

    /**
     * Export CSV Laporan (Sama seperti Admin tapi akses Manajemen)
     */
    public function exportLaporan(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();
        $filename  = "Laporan-Manajemen_" . $startDate->format('Ymd') . "-" . $endDate->format('Ymd') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        // ✅ UPDATE: Ambil Pengaturan Jam Kerja dari Database
        $workArea = WorkArea::find(1);
        $jamMasukBatas = '08:00:00'; // Default Fallback
        $hariKerjaAktif = [1, 2, 3, 4, 5]; // Default Senin-Jumat

        if ($workArea && !empty($workArea->jam_kerja)) {
            $config = $workArea->jam_kerja;
            if (isset($config['masuk'])) $jamMasukBatas = $config['masuk'] . ':00';
            if (isset($config['hari_kerja'])) $hariKerjaAktif = $config['hari_kerja'];
        }

        $listKaryawan = Employee::orderBy('nama')->get();

        $callback = function() use ($listKaryawan, $startDate, $endDate, $jamMasukBatas, $hariKerjaAktif) {
            $file = fopen('php://output', 'w');
            
            // Helper Sanitasi
            $sanitize = function ($value) {
                if (is_string($value) && preg_match('/^[\=\+\-\@]/', $value)) {
                    return "'" . $value;
                }
                return $value;
            };

            fputcsv($file, ['NIP', 'Nama', 'Hadir', 'Terlambat', 'Izin/Sakit', '% Kehadiran']);

            foreach ($listKaryawan as $karyawan) {
                $kehadiran = Attendance::where('emp_id', $karyawan->emp_id)
                    ->whereBetween('waktu_unggah', [$startDate, $endDate])
                    ->where('type', 'masuk')
                    ->get();

                $totalHadir = $kehadiran->count();
                
                // ✅ UPDATE: Hitung Terlambat Dinamis
                $totalTerlambat = $kehadiran->filter(function ($att) use ($jamMasukBatas) {
                    // Bandingkan jam unggah dengan jam masuk dari DB
                    return $att->waktu_unggah->format('H:i:s') > $jamMasukBatas;
                })->count();

                $totalIzinSakit = Leave::where('emp_id', $karyawan->emp_id)
                    ->where('status', 'disetujui')
                    ->where(function($q) use ($startDate, $endDate) {
                        $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_selesai', [$startDate, $endDate]);
                    })->count();

                // ✅ UPDATE: Hitung Hari Kerja Dinamis
                $countDays = 0;
                $curr = $startDate->copy();
                while ($curr->lte($endDate)) {
                    // Cek apakah hari ini (0-6) ada dalam daftar hari kerja aktif
                    if (in_array($curr->dayOfWeek, $hariKerjaAktif)) {
                        $countDays++;
                    }
                    $curr->addDay();
                }

                $persentase = $countDays > 0 ? ($totalHadir / $countDays) * 100 : 0;

                fputcsv($file, [
                    $sanitize($karyawan->nip),
                    $sanitize($karyawan->nama),
                    $totalHadir,
                    $totalTerlambat,
                    $totalIzinSakit,
                    number_format(min($persentase, 100), 1)
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}