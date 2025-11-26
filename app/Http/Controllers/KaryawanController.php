<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkArea;
use App\Models\Attendance;
use App\Models\Validation; // Pastikan Model Validation di-import
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Leave;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use App\Models\User; 
use App\Models\Employee;

class KaryawanController extends Controller
{
    private function getTodayAttendance() {
        $karyawanId = auth()->user()->employee->emp_id;
        $today = Carbon::today();

        // --- LOGIKA ABSEN ULANG ---
        // Kita cari absensi hari ini, TAPI kita filter:
        // Ambil yang status validasinya BUKAN 'Invalid' atau 'Rejected'.
        $absensiMasuk = Attendance::where('emp_id', $karyawanId)
            ->whereDate('waktu_unggah', $today)
            ->where('type', 'masuk')
            ->whereDoesntHave('validation', function($q) {
                $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
            })
            ->latest('waktu_unggah')
            ->first();

        $absensiPulang = Attendance::where('emp_id', $karyawanId)
            ->whereDate('waktu_unggah', $today)
            ->where('type', 'pulang')
            ->whereDoesntHave('validation', function($q) {
                $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
            })
            ->latest('waktu_unggah')
            ->first();

        // Cek apakah hari ini ada izin yang DISETUJUI
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

    // Di dalam Class KaryawanController

    public function checkExif(Request $request)
    {
        // Validasi input dasar
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:7000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'File foto tidak valid atau terlalu besar.'], 400);
        }

        $file = $request->file('foto_absensi');

        // --- A. CEK DUPLIKASI FILE (Hash) ---
        $fileHash = md5_file($file->getRealPath());
        $isDuplicate = Attendance::where('file_hash', $fileHash)->exists();
        if ($isDuplicate) {
            return response()->json(['status' => 'error', 'message' => 'Foto ini sudah pernah dipakai sebelumnya. Harap ambil foto baru!'], 400);
        }

        // --- B. BACA EXIF ---
        $exif = @exif_read_data($file->getRealPath());

        // Cek Kelengkapan GPS
        if (!$exif || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            return response()->json(['status' => 'error', 'message' => 'Data GPS tidak ditemukan pada foto. Pastikan fitur Lokasi/GPS aktif saat memotret.'], 400);
        }

        // Cek Waktu Pengambilan (Anti-Foto Lama)
        if (empty($exif['DateTimeOriginal'])) {
            return response()->json(['status' => 'error', 'message' => 'Tanggal foto tidak terdeteksi. Jangan gunakan foto hasil download/editan.'], 400);
        }

        try {
           $fotoTime = Carbon::parse($exif['DateTimeOriginal']);
           $serverTime = now();
           $diffInMinutes = $serverTime->diffInMinutes($fotoTime);

           if ($diffInMinutes > 15) {
               return response()->json(['status' => 'error', 'message' => 'Foto kadaluarsa (Diambil '.$diffInMinutes.' menit lalu). Harap ambil foto baru.'], 400);
           }
        } catch (\Exception $e) {
           return response()->json(['status' => 'error', 'message' => 'Format tanggal foto rusak.'], 400);
        }

        // Jika lolos semua cek EXIF
        return response()->json(['status' => 'success', 'message' => 'Validasi Foto Berhasil']);
    }

    public function dashboard() {
        $attendanceData = $this->getTodayAttendance();
        return view('karyawan.dashboard', $attendanceData);
    }

    public function unggah() {
        $data = $this->getTodayAttendance();

        // --- PERBAIKAN KOORDINAT (Menggunakan ST_AsText agar Anti-Tertukar) ---
        // Kita ambil raw text WKT, contoh: "POINT(106.822 -6.175)"
        // Ini memastikan kita parsing manual: Angka Pertama = Longitude, Angka Kedua = Latitude
        $workAreaQuery = WorkArea::select(
            'radius_geofence',
            'jam_kerja',
            DB::raw('ST_AsText(koordinat_pusat) as location_str')
        )->first(); // Gunakan first() bukan find(1) untuk jaga-jaga jika ID berubah

        // Parsing Manual WKT POINT(lng lat)
        $latitude = 0;
        $longitude = 0;

        if ($workAreaQuery && $workAreaQuery->location_str) {
            // Hapus "POINT(" dan ")"
            $cleanStr = str_replace(['POINT(', ')'], '', $workAreaQuery->location_str);
            $parts = explode(' ', $cleanStr);
            
            if (count($parts) == 2) {
                $longitude = (float) $parts[0]; // Urutan WKT standar selalu: X (Lng) Y (Lat)
                $latitude = (float) $parts[1];
            }
        }

        // Masukkan ke object agar bisa dibaca View
        $workAreaQuery->latitude = $latitude;
        $workAreaQuery->longitude = $longitude;
        
        $data['workArea'] = $workAreaQuery;

        return view('karyawan.unggah', $data);
    }

    public function showUploadForm(Request $request, $type) {
        if (!in_array($type, ['masuk', 'pulang'])) { abort(404); }
        return $this->unggah();
    }

    public function riwayat()
    {
        $user = auth()->user();
        $karyawan = $user->employee;

        // 1. Ambil Daftar Riwayat (Tabel) - HANYA ABSENSI
        $riwayatAbsensi = Attendance::with('validation')
            ->where('emp_id', $karyawan->emp_id)
            ->orderBy('waktu_unggah', 'desc')
            ->get();

        // 2. Statistik Angka (Kotak Atas)
        $izinCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'izin')->where('status', 'disetujui')->count();
        $sakitCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'sakit')->where('status', 'disetujui')->count();
        $cutiCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'cuti')->where('status', 'disetujui')->count();

        // Ambil data riwayat izin untuk ditampilkan
        $riwayatIzin = Leave::where('emp_id', $karyawan->emp_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. LOGIKA GRAFIK HARIAN (7 HARI TERAKHIR) - DIPERBAIKI
        $chartData = [];
        $chartLabels = [];

        // Loop 7 hari terakhir (termasuk hari ini), urutan mundur dari hari ini ke 6 hari lalu
        // Agar grafik terbaca dari kiri (terlama) ke kanan (terbaru), kita loop mundur lalu reverse atau loop maju
        // Di sini kita loop dari 6 hari lalu sampai hari ini (0)
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d M'); // Format: 26 Nov

            // Cek apakah karyawan HADIR (absen masuk) pada tanggal tersebut
            // Dan validasinya TIDAK Invalid/Rejected
            $isPresent = Attendance::where('emp_id', $karyawan->emp_id)
                ->whereDate('waktu_unggah', $date)
                ->where('type', 'masuk')
                ->whereDoesntHave('validation', function($q) {
                    $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
                })
                ->exists(); // Mengembalikan true/false

            // Jika hadir nilai 1, jika tidak 0 (untuk tinggi grafik)
            $chartData[] = $isPresent ? 1 : 0;
        }

        return view('karyawan.riwayat', compact(
            'karyawan', 'riwayatAbsensi', 'izinCount', 'sakitCount', 'cutiCount',
            'chartData', 'chartLabels', 'riwayatIzin'
        ));
    }

    public function izin()
    {
        $riwayatIzin = Leave::where('emp_id', auth()->user()->employee->emp_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // === LOGIKA BARU: AMBIL NO HP MANAJEMEN ===
        // Cari Employee yang User-nya memiliki role 'Manajemen'
        $manajemenEmp = Employee::whereHas('user', function($q) {
            $q->where('role', 'Manajemen');
        })->first();

        // Ambil no telepon jika ada, jika tidak set default
        $teleponManajemen = $manajemenEmp ? $manajemenEmp->no_telepon : null;

        return view('karyawan.izin', compact('riwayatIzin', 'teleponManajemen'));
    }

    public function storeIzin(Request $request)
    {
        // 1. VALIDASI INPUT DASAR
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

        // 2. CEK TUMPANG TINDIH (OVERLAPPING) IZIN
        $checkOverlap = Leave::where('emp_id', $empId)
            ->where('status', '!=', 'ditolak') // Hitung yg pending atau disetujui
            ->where(function($q) use ($request) {
                $start = $request->tanggal_mulai;
                $end = $request->tanggal_selesai;
                // Logika Overlap
                $q->whereBetween('tanggal_mulai', [$start, $end])
                  ->orWhereBetween('tanggal_selesai', [$start, $end])
                  ->orWhere(function($sub) use ($start, $end) {
                      $sub->where('tanggal_mulai', '<=', $start)
                          ->where('tanggal_selesai', '>=', $end);
                  });
            })
            ->first();

        if ($checkOverlap) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['tanggal_mulai' => 'Anda sudah memiliki pengajuan pada tanggal tersebut.']);
        }

        // 3. PROSES UPLOAD FILE
        $filePath = null;
        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $fileName = $empId . '-izin-' . now()->format('YmdHis') . '.' . $file->extension();
            $path = $file->storeAs('bukti_izin', $fileName, 'public');
            $filePath = Storage::url($path);
        }

        // 4. SIMPAN KE DATABASE
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

    public function profil()
    {
        $user = auth()->user();
        $employee = $user->employee;
        return view('karyawan.profil', compact('user', 'employee'));
    }

    public function updateProfil(Request $request)
    {
        $employee = auth()->user()->employee;

        $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|numeric|digits_between:10,15',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $employee->nama = $request->nama;
        $employee->alamat = $request->alamat;
        $employee->no_telepon = $request->no_telepon;

        if ($request->hasFile('foto_profil')) {
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
        return redirect()->route('karyawan.profil')->with('success', 'Profil berhasil diperbarui.');
    }

    public function deleteFotoProfil()
    {
        $employee = auth()->user()->employee;
        if ($employee->foto_profil) {
            $oldPath = str_replace('/storage/', '', $employee->foto_profil);
            Storage::disk('public')->delete($oldPath);
            $employee->foto_profil = null;
            $employee->save();
            return redirect()->back()->with('success', 'Foto profil berhasil dihapus.');
        }
        return redirect()->back()->with('error', 'Anda belum memiliki foto profil.');
    }

    public function storeFoto(Request $request)
    {
        // =========================================================================
        // LAYER 1: DEVICE LOCK
        // =========================================================================
        $deviceOwner = $request->cookie('device_owner_id');
        $currentUserId = auth()->user()->employee->emp_id;

        if ($deviceOwner && $deviceOwner != $currentUserId) {
            return redirect()->back()->with('error', 'KEAMANAN: Perangkat ini sudah terdaftar atas nama karyawan lain.');
        }

        // =========================================================================
        // LAYER 2: VALIDASI INPUT DASAR
        // =========================================================================
        $request->validate([
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:5000',
            'type'         => 'required|in:masuk,pulang',
            'browser_lat'  => 'required|numeric',
            'browser_lng'  => 'required|numeric',
        ]);

        $file = $request->file('foto_absensi');

        // =========================================================================
        // LAYER 3: CEK DUPLIKASI FILE
        // =========================================================================
        $fileHash = md5_file($file->getRealPath());
        if (Attendance::where('file_hash', $fileHash)->exists()) {
            return redirect()->back()->with('error', 'Foto ini sudah pernah digunakan sebelumnya. Harap ambil foto baru!');
        }

        // =========================================================================
        // LAYER 4: STRICT TIME CHECK
        // =========================================================================
        $exif = @exif_read_data($file->getRealPath());

        if (!isset($exif['DateTimeOriginal'])) {
            return redirect()->back()->with('error', 'Tanggal foto tidak terdeteksi. Gunakan kamera langsung.');
        }

        try {
            $fotoTime = Carbon::parse($exif['DateTimeOriginal']);
            if (now()->diffInMinutes($fotoTime) > 5) {
                return redirect()->back()->with('error', 'Foto kadaluarsa! Foto harus di-upload segera (Maksimal 5 menit).');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Format tanggal foto tidak valid.');
        }

        // =========================================================================
        // LAYER 5: SERVER-SIDE GEOFENCING (PARSING MANUAL WKT AGAR PRESISI)
        // =========================================================================
        $workArea = WorkArea::select(
            'area_id',
            'radius_geofence',
            'jam_kerja', 
            DB::raw('ST_AsText(koordinat_pusat) as location_str')
        )->first(); // Gunakan first() agar pasti dapat data

        if (!$workArea) {
            return redirect()->back()->with('error', 'Konfigurasi lokasi kantor belum diset.');
        }

        // Manual Parsing (Sama seperti method unggah)
        $cleanStr = str_replace(['POINT(', ')'], '', $workArea->location_str);
        $parts = explode(' ', $cleanStr);
        $officeLng = (float) $parts[0]; // X
        $officeLat = (float) $parts[1]; // Y

        $jarakMeters = $this->haversineDistance(
            $request->browser_lat,
            $request->browser_lng,
            $officeLat,
            $officeLng
        );

        // GEOFENCING CHECK (Server Side)
        if ($jarakMeters > $workArea->radius_geofence) {
            // DEBUG MESSAGE: Tampilkan detail koordinat agar user tahu salahnya dimana
            $debugMsg = "Jarak: " . round($jarakMeters) . "m (Max: {$workArea->radius_geofence}m). " .
                        "Posisi Anda: [{$request->browser_lat}, {$request->browser_lng}] " .
                        "Kantor: [$officeLat, $officeLng]";
            return redirect()->back()->with('error', "Gagal Geofencing: " . $debugMsg);
        }

        // EXIF Geolocation Check (Jika ada GPS di foto)
        $exifLat = isset($exif['GPSLatitude']) ? $this->gpsDmsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N') : null;
        $exifLng = isset($exif['GPSLongitude']) ? $this->gpsDmsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E') : null;

        if ($exifLat && $exifLng) {
            $jarakFoto = $this->haversineDistance($exifLat, $exifLng, $officeLat, $officeLng);
            if ($jarakFoto > ($workArea->radius_geofence + 500)) {
                 return redirect()->back()->with('error', 'Lokasi pada metadata Foto terdeteksi di luar kantor.');
            }
        }

        // =========================================================================
        // LAYER 6: CEK JAM KERJA (DENGAN DETEKSI TERLAMBAT & BLOKIR LATE ENTRY)
        // =========================================================================
        $now = Carbon::now();
        $jamKerjaConfig = $workArea->jam_kerja;
        
        $hariIni = $now->dayOfWeek; 
        $hariKerja = $jamKerjaConfig['hari_kerja'] ?? [1,2,3,4,5]; 
        
        if (!in_array($hariIni, $hariKerja)) {
            return redirect()->back()->with('error', 'Absensi Ditolak: Hari ini bukan jadwal hari kerja.');
        }

        $jamMasukBatas = $jamKerjaConfig['masuk'] ?? '08:00';
        $jamPulangBatas = $jamKerjaConfig['pulang'] ?? '17:00';

        $statusValidasi = 'Valid';
        $catatanValidasi = null;
        
        // A. Logika Masuk
        if ($request->type == 'masuk') {
            $jamMasukCarbon = Carbon::createFromTimeString($jamMasukBatas);
            $jamPulangCarbon = Carbon::createFromTimeString($jamPulangBatas);

            // 1. Cek jika sudah lewat JAM PULANG
            if ($now->greaterThan($jamPulangCarbon)) {
                 return redirect()->back()->with('error', 'Absensi Masuk ditolak! Jam kerja operasional hari ini telah berakhir pada pukul ' . $jamPulangBatas . '.');
            }
            
            // 2. Cek Terlalu Pagi
            if ($now->lessThan($jamMasukCarbon->copy()->subHours(2))) {
                 return redirect()->back()->with('error', 'Terlalu awal! Absen dibuka pukul ' . $jamMasukCarbon->subHours(2)->format('H:i'));
            }
            
            // 3. Cek Keterlambatan
            if ($now->greaterThan($jamMasukCarbon)) {
                $statusValidasi = 'Need Review'; 
                $catatanValidasi = 'Terlambat: Absen pukul ' . $now->format('H:i');
            }
        }
        
        // B. Logika Pulang
        if ($request->type == 'pulang') {
            $jamPulangCarbon = Carbon::createFromTimeString($jamPulangBatas);
            
            // Cek Pulang Cepat
            if ($now->lessThan($jamPulangCarbon)) {
                $statusValidasi = 'Need Review';
                $catatanValidasi = 'Pulang Cepat: Absen pukul ' . $now->format('H:i');
            }
        }

        // =========================================================================
        // LAYER 7: SIMPAN DATA ATTENDANCE
        // =========================================================================
        $fileName = $currentUserId . '-' . $now->format('Ymd-His') . '-' . $request->type . '.' . $file->extension();
        $path = $file->storeAs('public/absensi', $fileName);
        $publicPath = Storage::url($path);

        $attendance = Attendance::create([
            'emp_id' => $currentUserId,
            'area_id' => $workArea->area_id,
            'waktu_unggah' => $now,
            'latitude' => $exifLat ?? $request->browser_lat,
            'longitude' => $exifLng ?? $request->browser_lng,
            'nama_file_foto' => $publicPath,
            'timestamp_ekstraksi' => $exif['DateTimeOriginal'],
            'type' => $request->type,
            'file_hash' => $fileHash
        ]);

        Validation::create([
            'att_id' => $attendance->att_id,
            'status_validasi_otomatis' => $statusValidasi == 'Valid' ? 'Valid' : 'Need Review',
            'status_validasi_final' => $statusValidasi == 'Valid' ? 'Valid' : 'Pending',
            'catatan_admin' => $catatanValidasi, 
            'timestamp_validasi' => $now
        ]);

        $cookieLifetime = 2628000; 
        
        $msg = 'Absensi berhasil dicatat!';
        if ($statusValidasi != 'Valid') {
            $msg .= ' (Status: ' . $catatanValidasi . ')';
        }

        return redirect()->route('karyawan.dashboard')
            ->with('success', $msg)
            ->withCookie(cookie('device_owner_id', $currentUserId, $cookieLifetime));
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