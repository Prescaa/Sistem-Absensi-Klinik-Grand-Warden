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
<<<<<<< HEAD
}
=======

    // 1. HALAMAN UNGGAH
    public function showUnggah()
    {
        $user = auth()->user();
        if (!$user->employee) return back()->with('error', 'Akun Admin ini belum terhubung ke data Karyawan.');

        $today = Carbon::today();
        $absensiMasuk = Attendance::where('emp_id', $user->employee->emp_id)->whereDate('waktu_unggah', $today)->where('type', 'masuk')->first();
        $absensiPulang = Attendance::where('emp_id', $user->employee->emp_id)->whereDate('waktu_unggah', $today)->where('type', 'pulang')->first();
        
        $workArea = WorkArea::select('radius_geofence', DB::raw('ST_X(koordinat_pusat) as latitude'), DB::raw('ST_Y(koordinat_pusat) as longitude'))->first();

        return view('manajemen.absensi.unggah', compact('absensiMasuk', 'absensiPulang', 'workArea'));
    }

    public function storeFoto(Request $request)
    {
        $request->validate([
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'type'         => 'required|in:masuk,pulang',
            'browser_lat'  => 'required|numeric',
            'browser_lng'  => 'required|numeric',
        ]);

        $file = $request->file('foto_absensi');

        // --- 1. DETEKSI WAJAH (Python) ---
        if (! $this->detectFace($file->getRealPath())) {
            return redirect()->back()->with('error', 'VALIDASI WAJAH GAGAL: Sistem AI tidak menemukan wajah. Pastikan pencahayaan cukup.');
        }

        // --- 2. DEVICE LOCK ---
        $deviceOwner = $request->cookie('device_owner_id');
        $currentUserId = auth()->user()->employee->emp_id;
        if ($deviceOwner && $deviceOwner != $currentUserId) {
            return redirect()->back()->with('error', 'KEAMANAN: Perangkat ini terdaftar atas nama karyawan lain.');
        }

        // --- 3. DUPLIKASI FILE ---
        $fileHash = md5_file($file->getRealPath());
        if (Attendance::where('file_hash', $fileHash)->exists()) {
            return redirect()->back()->with('error', 'Foto ini sudah pernah digunakan sebelumnya.');
        }

        // --- 4. GEOFENCING ---
        $workArea = WorkArea::select(
            'area_id', 'radius_geofence',
            DB::raw('ST_X(koordinat_pusat) as latitude'),
            DB::raw('ST_Y(koordinat_pusat) as longitude')
        )->find(1);

        if (!$workArea) return redirect()->back()->with('error', 'Lokasi kantor belum diset.');

        $jarak = $this->haversineDistance($request->browser_lat, $request->browser_lng, $workArea->latitude, $workArea->longitude);

        if ($jarak > $workArea->radius_geofence) {
            return redirect()->back()->with('error', "Anda berada di luar jangkauan kantor ($jarak meter).");
        }

        // --- 5. SIMPAN DATA ---
        $fileName = $currentUserId . '-' . now()->format('Ymd-His') . '-' . $request->type . '.' . $file->extension();
        $path = $file->storeAs('public/absensi', $fileName);
        $publicPath = Storage::url($path);

        $exif = @exif_read_data($file->getRealPath());
        $exifLat = isset($exif['GPSLatitude']) ? $this->gpsDmsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N') : $request->browser_lat;
        $exifLng = isset($exif['GPSLongitude']) ? $this->gpsDmsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E') : $request->browser_lng;

        Attendance::create([
            'emp_id' => $currentUserId,
            'area_id' => $workArea->area_id,
            'waktu_unggah' => now(),
            'latitude' => $exifLat,
            'longitude' => $exifLng,
            'nama_file_foto' => $publicPath,
            'timestamp_ekstraksi' => $exif['DateTimeOriginal'] ?? now(),
            'type' => $request->type,
            'file_hash' => $fileHash
        ]);

        return redirect()->route('manajemen.absensi.riwayat')
            ->with('success', 'Absensi berhasil dicatat!')
            ->withCookie(cookie('device_owner_id', $currentUserId, 2628000));
    }

    // 3. HALAMAN RIWAYAT
public function showRiwayat()
    {
        $user = auth()->user();
        
        // Pastikan data karyawan ada
        if (!$user->employee) {
            return redirect()->route('manajemen.dashboard')->with('error', 'Data karyawan tidak ditemukan.');
        }
        
        $karyawan = $user->employee; 

        // 1. Ambil Data Absensi
        $riwayatAbsensi = Attendance::with('validation')
            ->where('emp_id', $karyawan->emp_id)
            ->orderBy('waktu_unggah', 'desc')
            ->get();
        
        // 2. Hitung Statistik
        $izinCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'izin')->where('status', 'disetujui')->count();
        $sakitCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'sakit')->where('status', 'disetujui')->count();
        $cutiCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'cuti')->where('status', 'disetujui')->count();

        // 3. ✅ [INI YANG KEMARIN HILANG] Ambil Data Riwayat Izin
        $riwayatIzin = Leave::where('emp_id', $karyawan->emp_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Kirim semua variabel ke View (tambahkan 'riwayatIzin')
        return view('manajemen.absensi.riwayat', compact(
            'riwayatAbsensi', 
            'karyawan', 
            'izinCount', 
            'sakitCount', 
            'cutiCount',
            'riwayatIzin' // <-- Wajib ada!
        ));
    }

    // 4. HALAMAN IZIN
    public function showIzin()
    {
        $riwayatIzin = Leave::where('emp_id', auth()->user()->employee->emp_id)->orderBy('created_at', 'desc')->get();
        return view('manajemen.absensi.izin', compact('riwayatIzin'));
    }

// --- FITUR PENGAJUAN IZIN ADMIN ---
    public function storeIzin(Request $request)
    {
        // Validasi Input
        $request->validate([
            'tipe_izin' => 'required|in:sakit,izin,cuti',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'deskripsi' => 'required|string|max:500',
            'file_bukti' => 'required_if:tipe_izin,sakit|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'file_bukti.required_if' => 'Wajib mengunggah bukti surat sakit jika mengajukan tipe Sakit.'
        ]);

        // Ambil ID Karyawan dari User yang Login
        $user = Auth::user();
        if (!$user->employee) {
            return redirect()->back()->with('error', 'Data karyawan tidak ditemukan. Hubungi IT.');
        }
        $empId = $user->employee->emp_id;

        // Cek Izin Ganda (Overlap)
        $checkOverlap = Leave::where('emp_id', $empId)
            ->where('status', '!=', 'ditolak')
            ->where(function($q) use ($request) {
                $start = $request->tanggal_mulai;
                $end = $request->tanggal_selesai;
                $q->whereBetween('tanggal_mulai', [$start, $end])
                  ->orWhereBetween('tanggal_selesai', [$start, $end])
                  ->orWhere(function($sub) use ($start, $end) {
                      $sub->where('tanggal_mulai', '<=', $start)->where('tanggal_selesai', '>=', $end);
                  });
            })
            ->first();

        if ($checkOverlap) {
            return redirect()->back()->withInput()->withErrors(['tanggal_mulai' => 'Anda sudah memiliki pengajuan pada tanggal tersebut.']);
        }

        // Proses Upload File Bukti
        $filePath = null;
        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $fileName = $empId . '-izin-' . now()->format('YmdHis') . '.' . $file->extension();
            $path = $file->storeAs('bukti_izin', $fileName, 'public');
            $filePath = Storage::url($path);
        }

        // Simpan ke Database
        Leave::create([
            'emp_id' => $empId,
            'tipe_izin' => $request->tipe_izin,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'deskripsi' => $request->deskripsi,
            'file_bukti' => $filePath,
            'status' => 'pending' // Default status
        ]);

        // Redirect Kembali ke Halaman Izin Admin
        return redirect()->route('manajemen.izin.show')->with('success', 'Pengajuan izin berhasil dikirim.');
    }
}
>>>>>>> 0de02d40c9dffccc6b9542015d2e8d4c901e8ee0
