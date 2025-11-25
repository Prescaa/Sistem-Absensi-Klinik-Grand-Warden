<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkArea;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Leave;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;

class KaryawanController extends Controller
{
    /**
     * Helper untuk mengambil data absensi hari ini.
     */
    private function getTodayAttendance() {
        $karyawanId = auth()->user()->employee->emp_id;
        $today = Carbon::today();

        // Absen Masuk
        $absensiMasuk = Attendance::where('emp_id', $karyawanId)
            ->whereDate('waktu_unggah', $today)
            ->where('type', 'masuk')
            ->whereDoesntHave('validation', function($q) {
                $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
            })
            ->latest('waktu_unggah')
            ->first();

        // Absen Pulang
        $absensiPulang = Attendance::where('emp_id', $karyawanId)
            ->whereDate('waktu_unggah', $today)
            ->where('type', 'pulang')
            ->whereDoesntHave('validation', function($q) {
                $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
            })
            ->latest('waktu_unggah')
            ->first();

        // Cek Izin Disetujui
        $todayLeave = Leave::where('emp_id', $karyawanId)
            ->where('status', 'disetujui')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->first();

        return [
            'absensiMasuk' => $absensiMasuk,
            'absensiPulang' => $absensiPulang,
            'todayLeave' => $todayLeave
        ];
    }

    /**
     * Endpoint AJAX untuk cek Validitas Foto & EXIF.
     */
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

        if (isset($exif['DateTimeOriginal'])) {
            try {
                $fotoTime = Carbon::parse($exif['DateTimeOriginal']);
                if (now()->diffInMinutes($fotoTime) > 15) {
                    return response()->json(['status' => 'error', 'message' => 'Foto kadaluarsa (Diambil >15 menit lalu). Harap ambil foto baru.'], 400);
                }
            } catch (\Exception $e) {
                // Abaikan error parsing tanggal
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Foto Valid.']);
    }

    public function dashboard() {
        $attendanceData = $this->getTodayAttendance();
        return view('karyawan.dashboard', $attendanceData);
    }

    public function unggah() {
        $data = $this->getTodayAttendance();
        $data['workArea'] = WorkArea::select(
                'radius_geofence',
                DB::raw('ST_X(koordinat_pusat) as latitude'),
                DB::raw('ST_Y(koordinat_pusat) as longitude')
            )->find(1);

        return view('karyawan.unggah', $data);
    }

    public function showUploadForm(Request $request, $type) {
        if (!in_array($type, ['masuk', 'pulang'])) { abort(404); }
        return $this->unggah();
    }

    // --- FITUR RIWAYAT ---
    public function riwayat()
    {
        $user = auth()->user();
        $karyawan = $user->employee;

        $riwayatAbsensi = Attendance::with('validation')
            ->where('emp_id', $karyawan->emp_id)
            ->orderBy('waktu_unggah', 'desc')
            ->get();

        $izinCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'izin')->where('status', 'disetujui')->count();
        $sakitCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'sakit')->where('status', 'disetujui')->count();
        $cutiCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'cuti')->where('status', 'disetujui')->count();

        $riwayatIzin = Leave::where('emp_id', $karyawan->emp_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $chartData = [];
        $chartLabels = ['M1', 'M2', 'M3', 'M4'];

        for ($i = 3; $i >= 0; $i--) {
            $startWeek = Carbon::now()->subWeeks($i)->startOfWeek();
            $endWeek   = Carbon::now()->subWeeks($i)->endOfWeek();

            $count = Attendance::where('emp_id', $karyawan->emp_id)
                ->whereBetween('waktu_unggah', [$startWeek, $endWeek])
                ->where('type', 'masuk')
                ->whereHas('validation', function($q) { $q->where('status_validasi_final', 'Valid'); })
                ->count();

            $chartData[] = $count;
        }

        return view('karyawan.riwayat', compact('karyawan', 'riwayatAbsensi', 'izinCount', 'sakitCount', 'cutiCount', 'chartData', 'chartLabels', 'riwayatIzin'));
    }

    // --- FITUR IZIN ---
    public function izin()
    {
        $riwayatIzin = Leave::where('emp_id', auth()->user()->employee->emp_id)->orderBy('created_at', 'desc')->get();
        return view('karyawan.izin', compact('riwayatIzin'));
    }

    public function storeIzin(Request $request)
    {
        $request->validate([
            'tipe_izin' => 'required|in:sakit,izin,cuti',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'deskripsi' => 'required|string|max:500',
            'file_bukti' => 'required_if:tipe_izin,sakit|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'file_bukti.required_if' => 'Wajib mengunggah bukti surat sakit jika mengajukan tipe Sakit.'
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

        return redirect()->route('karyawan.izin')->with('success', 'Pengajuan izin berhasil dikirim.');
    }

    // --- FITUR PROFIL ---
    public function profil()
    {
        $user = auth()->user();
        $employee = $user->employee;
        return view('karyawan.profil', compact('user', 'employee'));
    }

    // =========================================================================
    // ✅ FUNGSI UPDATE PROFIL (GABUNGAN PERBAIKAN HAPUS FOTO)
    // =========================================================================
    public function updateProfil(Request $request)
    {
        $employee = auth()->user()->employee;

        // 1. Validasi (Pastikan 'hapus_foto' termasuk nullable agar terbaca)
        $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|numeric|digits_between:10,15',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'hapus_foto' => 'nullable', // WAJIB ADA
        ]);

        $employee->nama = $request->nama;
        $employee->alamat = $request->alamat;
        $employee->no_telepon = $request->no_telepon;

        // 2. LOGIKA HAPUS FOTO (Dahulukan sebelum upload baru)
        // Menggunakan input() dan operator == '1'
        if ($request->input('hapus_foto') == '1') {
            if ($employee->foto_profil) {
                $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                Storage::disk('public')->delete($oldPath);
            }
            $employee->foto_profil = null;
        }

        // 3. LOGIKA UPLOAD FOTO BARU
        if ($request->hasFile('foto_profil')) {
            // Hapus foto lama jika belum terhapus di langkah 2
            if ($employee->foto_profil) {
                $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                Storage::disk('public')->delete($oldPath);
            }
            
            $file = $request->file('foto_profil');
            $fileName = $employee->emp_id . '-profil-' . now()->format('YmdHis') . '.' . $file->extension();
            $path = $file->storeAs('foto_profil', $fileName, 'public');
            $employee->foto_profil = Storage::url($path);
        }

        $employee->save();
        
        $successMessage = 'Profil berhasil diperbarui.';
        if ($request->input('hapus_foto') == '1' && !$request->hasFile('foto_profil')) {
            $successMessage .= ' Foto profil telah dihapus.';
        }

        return redirect()->route('karyawan.profil')->with('success', $successMessage);
    }

    public function deleteFotoProfil()
    {
        // Method ini dipertahankan untuk kompatibilitas route lama (jika ada)
        return redirect()->route('karyawan.profil')->with('error', 'Method tidak digunakan. Gunakan tombol hapus dan simpan perubahan.');
    }

    // =========================================================================
    // FUNGSI UTAMA: PROSES SIMPAN ABSENSI (Face Detect & GPS)
    // =========================================================================
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

        return redirect()->route('karyawan.dashboard')
            ->with('success', 'Absensi berhasil dicatat!')
            ->withCookie(cookie('device_owner_id', $currentUserId, 2628000));
    }

    /**
     * Helper: Deteksi Wajah via Python (OpenCV)
     */
    private function detectFace($imagePath)
    {
        try {
            $scriptPath = base_path('app/Python/detect_face.py');
            // Gunakan 'python' untuk Windows, 'python3' untuk Linux/Mac
            $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
            
            $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($imagePath) . " 2>&1";
            $output = shell_exec($command);
            $result = trim($output);

            if ($result === 'true') return true;
            
            // Jika output tidak 'true' (misal 'false' atau error message), anggap gagal
            Log::warning("Face Detect Failed/Error: " . $result);
            return false;

        } catch (\Exception $e) {
            Log::error("Face Detect Exception: " . $e->getMessage());
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
}