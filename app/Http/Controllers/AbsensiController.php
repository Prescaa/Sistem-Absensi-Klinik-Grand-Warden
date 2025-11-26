<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkArea;
use App\Models\Attendance;
use App\Models\Leave;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    /**
     * [PAGE] Menampilkan Form Upload Absensi
     * Sebelumnya: unggah()
     */
    public function create()
    {
        $data = $this->getTodayAttendance();

        // Mengambil data area kantor (Latitude/Longitude Pusat & Radius)
        $data['workArea'] = WorkArea::select(
                'radius_geofence',
                DB::raw('ST_Y(koordinat_pusat) as latitude'),  // ST_Y adalah Latitude
                DB::raw('ST_X(koordinat_pusat) as longitude')  // ST_X adalah Longitude
            )->find(1); // Asumsi ID area kantor utama adalah 1

        // Kita arahkan ke view baru yang akan kita buat nanti (absensi.create)
        // atau Anda bisa sementara menggunakan 'karyawan.unggah' jika belum membuat view baru
        return view('absensi.create', $data);
    }

    /**
     * [ACTION] Memproses Penyimpanan Absensi (Foto, Wajah, GPS)
     * Sebelumnya: storeFoto()
     */
    public function store(Request $request)
    {
        $request->validate([
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'type'         => 'required|in:masuk,pulang',
            'browser_lat'  => 'required|numeric',
            'browser_lng'  => 'required|numeric',
        ]);

        $user = auth()->user();

        // Pastikan User terhubung dengan data Employee (Sesuai Fase 1)
        if (!$user->employee) {
            return redirect()->back()->with('error', 'Akun Anda belum terhubung dengan data karyawan. Hubungi IT.');
        }

        $currentUserId = $user->employee->emp_id;
        $file = $request->file('foto_absensi');

        // 1. DETEKSI WAJAH (Python Script)
        // Script ini memeriksa apakah ada wajah manusia di foto
        if (! $this->detectFace($file->getRealPath())) {
            return redirect()->back()->with('error', 'VALIDASI WAJAH GAGAL: Wajah tidak terdeteksi. Pastikan pencahayaan cukup dan wajah terlihat jelas.');
        }

        // 2. DEVICE LOCK (Kunci Perangkat)
        // Mencegah titip absen (login akun teman di HP sendiri)
        $deviceOwner = $request->cookie('device_owner_id');
        if ($deviceOwner && $deviceOwner != $currentUserId) {
            return redirect()->back()->with('error', 'KEAMANAN: Perangkat ini terdaftar atas nama karyawan lain. Gunakan perangkat Anda sendiri.');
        }

        // 3. CEK DUPLIKASI FILE (Hash Check)
        // Mencegah upload ulang foto lama yang sama persis
        $fileHash = md5_file($file->getRealPath());
        if (Attendance::where('file_hash', $fileHash)->exists()) {
            return redirect()->back()->with('error', 'Foto ini sudah pernah digunakan sebelumnya. Harap ambil foto baru.');
        }

        // 4. GEOFENCING (Cek Jarak)
        $workArea = WorkArea::select(
            'area_id', 'radius_geofence',
            DB::raw('ST_Y(koordinat_pusat) as latitude'),  // ST_Y adalah Latitude
            DB::raw('ST_X(koordinat_pusat) as longitude')
        )->find(1);

        if (!$workArea) {
            return redirect()->back()->with('error', 'Lokasi kantor belum diset oleh Admin.');
        }

        $jarak = $this->haversineDistance($request->browser_lat, $request->browser_lng, $workArea->latitude, $workArea->longitude);

        if ($jarak > $workArea->radius_geofence) {
            return redirect()->back()->with('error', "Anda berada di luar jangkauan kantor. Jarak: " . round($jarak) . " meter (Maks: $workArea->radius_geofence m).");
        }

        // 5. PROSES SIMPAN FILE & EXIF
        $fileName = $currentUserId . '-' . now()->format('Ymd-His') . '-' . $request->type . '.' . $file->extension();
        $path = $file->storeAs('public/absensi', $fileName);
        $publicPath = Storage::url($path);

        // Baca Metadata EXIF (GPS dari Foto) jika ada
        $exif = @exif_read_data($file->getRealPath());

        // Gunakan GPS Browser sebagai fallback jika GPS Foto tidak terbaca
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

        // Redirect ke halaman riwayat agar universal (bisa dilihat admin/manajemen/karyawan)
        return redirect()->route('absensi.riwayat')
            ->with('success', 'Absensi berhasil dicatat!')
            ->withCookie(cookie('device_owner_id', $currentUserId, 2628000)); // Cookie berlaku 1 bulan
    }

    /**
     * [PAGE] Menampilkan Riwayat Absensi Pribadi
     * Sebelumnya: riwayat()
     */
    public function riwayat()
    {
        $user = auth()->user();
        if (!$user->employee) {
            return redirect()->back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $karyawan = $user->employee;

        // Ambil data absensi milik user yang sedang login
        $riwayatAbsensi = Attendance::with('validation')
            ->where('emp_id', $karyawan->emp_id)
            ->orderBy('waktu_unggah', 'desc')
            ->get();

        // Hitung statistik cuti/izin/sakit
        $izinCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'izin')->where('status', 'disetujui')->count();
        $sakitCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'sakit')->where('status', 'disetujui')->count();
        $cutiCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'cuti')->where('status', 'disetujui')->count();

        return view('absensi.riwayat', compact('karyawan', 'riwayatAbsensi', 'izinCount', 'sakitCount', 'cutiCount'));
    }

    /**
     * [AJAX] Endpoint Cek Validitas EXIF sebelum submit
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

        // Cek hash duplikasi
        $fileHash = md5_file($file->getRealPath());
        if (Attendance::where('file_hash', $fileHash)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Foto ini sudah pernah dipakai sebelumnya.'], 400);
        }

        // Cek EXIF
        if (!function_exists('exif_read_data')) {
            return response()->json(['status' => 'success', 'message' => 'Warning: Ekstensi EXIF Server non-aktif.']);
        }

        $exif = @exif_read_data($file->getRealPath());
        if (!$exif || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Lokasi (GPS) tidak ditemukan pada foto. Pastikan GPS kamera aktif.'
            ], 400);
        }

        // Cek Timestamp Foto (maks 15 menit yang lalu)
        if (isset($exif['DateTimeOriginal'])) {
            try {
                $fotoTime = Carbon::parse($exif['DateTimeOriginal']);
                if (now()->diffInMinutes($fotoTime) > 15) {
                    return response()->json(['status' => 'error', 'message' => 'Foto kadaluarsa (Diambil >15 menit lalu). Harap ambil foto baru.'], 400);
                }
            } catch (\Exception $e) { }
        }

        return response()->json(['status' => 'success', 'message' => 'Foto Valid.']);
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    private function getTodayAttendance() {
        $user = auth()->user();
        if (!$user->employee) return [];

        $karyawanId = $user->employee->emp_id;
        $today = Carbon::today();

        $absensiMasuk = Attendance::where('emp_id', $karyawanId)
            ->whereDate('waktu_unggah', $today)
            ->where('type', 'masuk')
            ->whereDoesntHave('validation', function($q) {
                $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
            })
            ->latest('waktu_unggah')->first();

        $absensiPulang = Attendance::where('emp_id', $karyawanId)
            ->whereDate('waktu_unggah', $today)
            ->where('type', 'pulang')
            ->whereDoesntHave('validation', function($q) {
                $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
            })
            ->latest('waktu_unggah')->first();

        return compact('absensiMasuk', 'absensiPulang');
    }

    private function detectFace($imagePath)
    {
        try {
            // Path ke script Python Anda (Sesuai struktur file yang ada)
            $scriptPath = base_path('app/Python/detect_face.py');

            // Deteksi OS untuk perintah python yang tepat
            $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';

            $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($imagePath) . " 2>&1";
            $output = shell_exec($command);
            $result = trim($output);

            if ($result === 'true') return true;

            Log::warning("Face Detect Failed: " . $result);
            return false;

        } catch (\Exception $e) {
            Log::error("Face Detect Exception: " . $e->getMessage());
            return false; // Fail-safe: jika error script, anggap gagal demi keamanan
        }
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Radius bumi dalam meter
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
        return ($ref == 'S' || $ref == 'W') ? -$decimal : $decimal;
    }
}
