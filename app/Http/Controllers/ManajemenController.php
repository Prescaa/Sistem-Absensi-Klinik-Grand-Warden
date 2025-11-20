<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
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
        // Ini fitur khusus Manajemen sesuai Use Case UC-009
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

        $listKaryawan = Employee::orderBy('nama')->get();

        $callback = function() use ($listKaryawan, $startDate, $endDate) {
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
                
                $totalTerlambat = $kehadiran->filter(function ($att) {
                    return $att->waktu_unggah->format('H:i:s') > '08:00:00';
                })->count();

                $totalIzinSakit = Leave::where('emp_id', $karyawan->emp_id)
                    ->where('status', 'disetujui')
                    ->where(function($q) use ($startDate, $endDate) {
                        $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_selesai', [$startDate, $endDate]);
                    })->count();

                // Hitung hari kerja (Senin-Jumat)
                $countDays = 0;
                $curr = $startDate->copy();
                while ($curr->lte($endDate)) {
                    if (!$curr->isWeekend()) $countDays++;
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