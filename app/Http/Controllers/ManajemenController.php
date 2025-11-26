<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\WorkArea;
use App\Models\Validation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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

        // Hitung kehadiran hari ini (unik per orang)
        $presentCount = Attendance::whereDate('waktu_unggah', $today)
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
                ->whereDoesntHave('validation', function ($q) {
                    $q->where('status_validasi_final', 'Invalid');
                })
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
     * Halaman Validasi (Absensi & Izin)
     */
    public function showValidasiPage()
    {
        // Ambil absensi yang BELUM punya validasi (Karyawan biasa)
        // ATAU absensi yang SUDAH punya validasi TAPI statusnya masih 'Pending' (Kasus Manajer)
        $pendingAttendances = Attendance::with('employee')
            ->where(function($query) {
                // Kondisi 1: Belum ada row validasi sama sekali
                $query->whereDoesntHave('validation')
                // Kondisi 2: Ada row validasi, tapi status finalnya Pending
                      ->orWhereHas('validation', function($q) {
                          $q->where('status_validasi_final', 'Pending');
                      });
            })
            ->orderBy('waktu_unggah', 'asc') // Yang terlama di atas agar segera diproses
            ->get();

        $pendingLeaves = Leave::where('status', 'pending')
            ->with('employee')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('manajemen.validasi', [
            'pendingAbsensi' => $pendingAttendances,
            'pendingIzin' => $pendingLeaves
        ]);
    }

    /**
     * Submit Validasi Absensi
     */
    public function submitValidasi(Request $request)
    {
        $request->validate([
            'att_id' => 'required|exists:ATTENDANCE,att_id',
            'status_validasi' => 'required|in:Valid,Invalid',
            'catatan_validasi' => 'nullable|string|max:500'
        ]);

        $att = Attendance::findOrFail($request->att_id);

        // PROTEKSI: Manajer tidak boleh memvalidasi absensinya sendiri
        if ($att->emp_id == Auth::user()->employee->emp_id) {
            return redirect()->back()->with('error', 'AKSES DITOLAK: Anda tidak dapat memvalidasi absensi milik sendiri.');
        }

        // Update atau Create data validasi
        Validation::updateOrCreate(
            ['att_id' => $request->att_id],
            [
                'admin_id' => Auth::user()->employee->emp_id,
                'status_validasi_otomatis' => $request->status_validasi, // Override status AI
                'status_validasi_final' => $request->status_validasi,
                'catatan_admin' => $request->catatan_validasi,
                'timestamp_validasi' => now()
            ]
        );

        return redirect()->back()->with('success', 'Validasi absensi berhasil disimpan.');
    }

    /**
     * Submit Approval Izin
     */
    public function submitValidasiIzin(Request $request)
    {
        $request->validate([
            'leave_id' => 'required|exists:leaves,leave_id',
            'status' => 'required|in:disetujui,ditolak',
            'catatan_admin' => 'nullable|string|max:500',
        ]);

        $leave = Leave::findOrFail($request->leave_id);

        // PROTEKSI: Manajer tidak boleh menyetujui izin sendiri
        if ($leave->emp_id == Auth::user()->employee->emp_id) {
            return redirect()->back()->with('error', 'AKSES DITOLAK: Anda tidak dapat menyetujui izin milik sendiri.');
        }

        $leave->status = $request->status;
        $leave->catatan_admin = $request->catatan_admin;
        $leave->save();

        $pesan = $request->status == 'disetujui' ? 'Izin berhasil disetujui.' : 'Izin telah ditolak.';
        return redirect()->back()->with('success', $pesan);
    }

    /**
     * Menampilkan Halaman Tabel Laporan dengan FILTER & SORTING
     */
    public function showLaporanPage(Request $request)
    {
        $query = Attendance::with(['employee', 'validation']);

        // 1. Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('waktu_unggah', [
                Carbon::parse($request->start_date)->startOfDay(), 
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        } else {
            // Default: Bulan ini
            $query->whereMonth('waktu_unggah', Carbon::now()->month);
        }

        // 2. Fitur Sorting (Terbaru / Terlama)
        $sortOrder = $request->input('sort_by', 'desc'); // Default Descending (Terbaru)
        $query->orderBy('waktu_unggah', $sortOrder);

        $attendances = $query->get();

        return view('manajemen.laporan', compact('attendances'));
    }

    /**
     * Export CSV Laporan
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

        // Ambil Konfigurasi Jam Kerja
        $workArea = WorkArea::find(1);
        $jamMasukBatas = '08:00:00';
        $hariKerjaAktif = [1, 2, 3, 4, 5];

        if ($workArea && !empty($workArea->jam_kerja)) {
            $config = $workArea->jam_kerja;
            if (isset($config['masuk'])) $jamMasukBatas = $config['masuk'] . ':00';
            if (isset($config['hari_kerja'])) $hariKerjaAktif = $config['hari_kerja'];
        }

        $listKaryawan = Employee::orderBy('nama')->get();

        $callback = function() use ($listKaryawan, $startDate, $endDate, $jamMasukBatas, $hariKerjaAktif) {
            $file = fopen('php://output', 'w');

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

                $totalTerlambat = $kehadiran->filter(function ($att) use ($jamMasukBatas) {
                    return $att->waktu_unggah->format('H:i:s') > $jamMasukBatas;
                })->count();

                $totalIzinSakit = Leave::where('emp_id', $karyawan->emp_id)
                    ->where('status', 'disetujui')
                    ->where(function($q) use ($startDate, $endDate) {
                        $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_selesai', [$startDate, $endDate]);
                    })->count();

                $countDays = 0;
                $curr = $startDate->copy();
                while ($curr->lte($endDate)) {
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

    // 1. HALAMAN UNGGAH (MANAJEMEN)
    public function showUnggah()
    {
        $user = auth()->user();
        if (!$user->employee) return back()->with('error', 'Akun Manajemen ini belum terhubung ke data Karyawan.');

        $today = Carbon::today();
        $absensiMasuk = Attendance::where('emp_id', $user->employee->emp_id)->whereDate('waktu_unggah', $today)->where('type', 'masuk')->first();
        $absensiPulang = Attendance::where('emp_id', $user->employee->emp_id)->whereDate('waktu_unggah', $today)->where('type', 'pulang')->first();

        $workArea = WorkArea::select(
            'radius_geofence',
            'jam_kerja',
            DB::raw('ST_X(koordinat_pusat) as latitude'),
            DB::raw('ST_Y(koordinat_pusat) as longitude')
        )->first();

        return view('manajemen.absensi.unggah', compact('absensiMasuk', 'absensiPulang', 'workArea'));
    }

    // 2. SIMPAN ABSENSI
    public function storeFoto(Request $request)
    {
        $request->validate([
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'type'         => 'required|in:masuk,pulang',
            'browser_lat'  => 'required|numeric',
            'browser_lng'  => 'required|numeric',
        ]);

        $file = $request->file('foto_absensi');

        if (! $this->detectFace($file->getRealPath())) {
            return redirect()->back()->with('error', 'VALIDASI WAJAH GAGAL: Sistem AI tidak menemukan wajah.');
        }

        $deviceOwner = $request->cookie('device_owner_id');
        $currentUserId = auth()->user()->employee->emp_id;
        if ($deviceOwner && $deviceOwner != $currentUserId) {
            return redirect()->back()->with('error', 'KEAMANAN: Perangkat ini terdaftar atas nama karyawan lain.');
        }

        $fileHash = md5_file($file->getRealPath());
        if (Attendance::where('file_hash', $fileHash)->exists()) {
            return redirect()->back()->with('error', 'Foto ini sudah pernah digunakan sebelumnya.');
        }

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
        if (!$user->employee) return redirect()->route('manajemen.dashboard')->with('error', 'Data karyawan tidak ditemukan.');

        $karyawan = $user->employee;

        $riwayatAbsensi = Attendance::with('validation')
            ->where('emp_id', $karyawan->emp_id)
            ->orderBy('waktu_unggah', 'desc')
            ->get();

        $izinCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'izin')->where('status', 'disetujui')->count();
        $sakitCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'sakit')->where('status', 'disetujui')->count();
        $cutiCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'cuti')->where('status', 'disetujui')->count();

        $riwayatIzin = Leave::where('emp_id', $karyawan->emp_id)->orderBy('created_at', 'desc')->get();

        return view('manajemen.absensi.riwayat', compact(
            'riwayatAbsensi', 'karyawan', 'izinCount', 'sakitCount', 'cutiCount', 'riwayatIzin'
        ));
    }

    // 4. HALAMAN IZIN
    public function showIzin()
    {
        $riwayatIzin = Leave::where('emp_id', auth()->user()->employee->emp_id)->orderBy('created_at', 'desc')->get();
        return view('manajemen.absensi.izin', compact('riwayatIzin'));
    }

    // 5. SIMPAN PENGAJUAN IZIN
    public function storeIzin(Request $request)
    {
        $request->validate([
            'tipe_izin' => 'required|in:sakit,izin,cuti',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'deskripsi' => 'required|string|max:500',
            'file_bukti' => 'required_if:tipe_izin,sakit|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $empId = auth()->user()->employee->emp_id;

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

        $filePath = null;
        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $fileName = $empId . '-izin-' . now()->format('YmdHis') . '.' . $file->extension();
            $path = $file->storeAs('bukti_izin', $fileName, 'public');
            $filePath = Storage::url($path);
        }

        Leave::create([
            'emp_id' => $empId,
            'tipe_izin' => $request->tipe_izin,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'deskripsi' => $request->deskripsi,
            'file_bukti' => $filePath,
            'status' => 'pending'
        ]);

        return redirect()->route('manajemen.izin.show')->with('success', 'Pengajuan izin berhasil dikirim.');
    }

    // --- HELPER METHODS ---

    private function detectFace($imagePath)
    {
        try {
            $scriptPath = base_path('app/Python/detect_face.py');
            $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
            $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($imagePath) . " 2>&1";
            $output = shell_exec($command);
            $result = trim($output);
            return $result === 'true';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    private function gpsDmsToDecimal($dmsArray, $ref) {
        $evalCoordPart = function ($coordPart) {
            $parts = explode('/', $coordPart);
            if (count($parts) == 2) { return $parts[1] == 0 ? 0 : $parts[0] / $parts[1]; }
            return (float)$parts[0];
        };
        $degrees = $evalCoordPart($dmsArray[0]);
        $minutes = $evalCoordPart($dmsArray[1]);
        $seconds = $evalCoordPart($dmsArray[2]);
        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
        if ($ref == 'S' || $ref == 'W') { return -$decimal; }
        return $decimal;
    }

    public function checkExif(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'File terlalu besar (>10MB) atau bukan gambar.'], 422);
        }

        $file = $request->file('foto_absensi');
        $fileHash = md5_file($file->getRealPath());

        if (Attendance::where('file_hash', $fileHash)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Foto ini sudah pernah dipakai sebelumnya.'], 400);
        }

        if (!function_exists('exif_read_data')) {
            return response()->json(['status' => 'success', 'message' => 'Warning: EXIF Server non-aktif, validasi dilewati.']);
        }

        $exif = @exif_read_data($file->getRealPath());

        if (!$exif || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Lokasi (GPS) tidak ditemukan pada foto. Pastikan GPS kamera aktif.'
            ], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'Foto Valid.']);
    }
}