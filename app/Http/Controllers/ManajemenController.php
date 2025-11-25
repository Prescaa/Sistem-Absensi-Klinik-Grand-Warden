<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Validation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

    public function showValidasiPage()
    {
        // 1. Ambil Absensi Pending
        $pendingAttendances = Attendance::whereDoesntHave('validation')
            ->with('employee')
            ->orderBy('waktu_unggah', 'desc')
            ->get();

        // 2. Ambil Pengajuan Izin Pending
        $pendingLeaves = Leave::with('employee')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mengembalikan view khusus manajemen
        return view('manajemen.validasi', [
            'attendances' => $pendingAttendances,
            'leaves' => $pendingLeaves
        ]);
    }

    public function submitValidasi(Request $request)
    {
        $request->validate([
            'att_id' => 'required|exists:ATTENDANCE,att_id',
            'status_validasi' => 'required|in:Valid,Invalid',
            'catatan_validasi' => 'nullable|string|max:500'
        ]);

        // Ambil ID Karyawan dari user Manajemen yang sedang login
        $manajemenEmpId = Auth::user()->employee->emp_id;

        Validation::create([
            'att_id' => $request->att_id,
            'admin_id' => $manajemenEmpId, // Disimpan sebagai validator
            'status_validasi_otomatis' => $request->status_validasi,
            'status_validasi_final' => $request->status_validasi,
            'catatan_admin' => $request->catatan_validasi,
            'timestamp_validasi' => now()
        ]);

        return redirect()->route('manajemen.validasi.show')
                         ->with('success', 'Validasi absensi berhasil disimpan.');
    }

    public function submitValidasiIzin(Request $request)
    {
        $request->validate([
            'leave_id' => 'required|exists:leaves,leave_id',
            'status' => 'required|in:disetujui,ditolak',
            'catatan_admin' => 'nullable|string|max:500',
        ]);

        $leave = Leave::findOrFail($request->leave_id);
        $leave->status = $request->status;
        $leave->catatan_admin = $request->catatan_admin;
        $leave->save();

        $pesan = $request->status == 'disetujui' ? 'Izin berhasil disetujui.' : 'Izin telah ditolak.';

        return redirect()->route('manajemen.validasi.show')
                         ->with('success', $pesan);
    }

    /**
     * ✅ FITUR BARU: Menampilkan Halaman Tabel Laporan
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

        $attendances = $query->get(); // Bisa diganti ->paginate(10) jika data banyak

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
