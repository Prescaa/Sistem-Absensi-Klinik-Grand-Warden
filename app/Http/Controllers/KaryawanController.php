<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkArea;
use App\Models\Attendance;
use App\Models\Validation; 
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

        // Logika: Ambil absensi hari ini yang status validasinya TIDAK 'Invalid' atau 'Rejected'
        // Jika status masih 'Pending', tetap dianggap sudah absen (menunggu).
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

    public function checkExif(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:7000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'File foto tidak valid atau terlalu besar.'], 400);
        }

        $file = $request->file('foto_absensi');

        $fileHash = md5_file($file->getRealPath());
        $isDuplicate = Attendance::where('file_hash', $fileHash)->exists();
        if ($isDuplicate) {
            return response()->json(['status' => 'error', 'message' => 'Foto ini sudah pernah dipakai sebelumnya. Harap ambil foto baru!'], 400);
        }

        $exif = @exif_read_data($file->getRealPath());

        if (!$exif || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            return response()->json(['status' => 'error', 'message' => 'Data GPS tidak ditemukan pada foto. Pastikan fitur Lokasi/GPS aktif saat memotret.'], 400);
        }

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

        return response()->json(['status' => 'success', 'message' => 'Validasi Foto Berhasil']);
    }

    public function dashboard() {
        $attendanceData = $this->getTodayAttendance();
        return view('karyawan.dashboard', $attendanceData);
    }

    public function unggah() {
        $data = $this->getTodayAttendance();

        $workAreaQuery = WorkArea::select(
            'radius_geofence',
            'jam_kerja',
            DB::raw('ST_AsText(koordinat_pusat) as location_str')
        )->first(); 

        $latitude = 0;
        $longitude = 0;

        if ($workAreaQuery && $workAreaQuery->location_str) {
            $cleanStr = str_replace(['POINT(', ')'], '', $workAreaQuery->location_str);
            $parts = explode(' ', $cleanStr);
            
            if (count($parts) == 2) {
                $longitude = (float) $parts[0]; 
                $latitude = (float) $parts[1];  
            }
        }

        if ($workAreaQuery) {
            $workAreaQuery->latitude = $latitude;
            $workAreaQuery->longitude = $longitude;
        }
        
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
        $chartLabels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d M'); 

            $isPresent = Attendance::where('emp_id', $karyawan->emp_id)
                ->whereDate('waktu_unggah', $date)
                ->where('type', 'masuk')
                ->whereDoesntHave('validation', function($q) {
                    $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
                })
                ->exists(); 

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

        $manajemenEmp = Employee::whereHas('user', function($q) {
            $q->where('role', 'Manajemen');
        })->first();

        $teleponManajemen = $manajemenEmp ? $manajemenEmp->no_telepon : null;

        return view('karyawan.izin', compact('riwayatIzin', 'teleponManajemen'));
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

    // =========================================================================
    // STORE FOTO (LOGIKA UTAMA ABSENSI)
    // =========================================================================
    public function storeFoto(Request $request)
    {
        // 1. DEVICE LOCK
        $deviceOwner = $request->cookie('device_owner_id');
        $currentUserId = auth()->user()->employee->emp_id;

        if ($deviceOwner && $deviceOwner != $currentUserId) {
            return redirect()->back()->with('error', 'KEAMANAN: Perangkat ini sudah terdaftar atas nama karyawan lain.');
        }

        // 2. VALIDASI INPUT
        $request->validate([
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:5000',
            'type'         => 'required|in:masuk,pulang',
            'browser_lat'  => 'required|numeric',
            'browser_lng'  => 'required|numeric',
        ]);

        $file = $request->file('foto_absensi');

        // 3. DETEKSI WAJAH
        if (! $this->detectFace($file->getRealPath())) {
            return redirect()->back()->with('error', 'VALIDASI WAJAH GAGAL: Sistem AI tidak menemukan wajah.');
        }

        // 4. CEK DUPLIKASI
        $fileHash = md5_file($file->getRealPath());
        if (Attendance::where('file_hash', $fileHash)->exists()) {
            return redirect()->back()->with('error', 'Foto ini sudah pernah digunakan sebelumnya.');
        }

        // 5. STRICT TIME CHECK (EXIF)
        $exif = @exif_read_data($file->getRealPath());
        if (!isset($exif['DateTimeOriginal'])) {
            return redirect()->back()->with('error', 'Tanggal foto tidak terdeteksi. Pastikan menggunakan kamera langsung.');
        }
        try {
            $fotoTime = Carbon::parse($exif['DateTimeOriginal']);
            if (now()->diffInMinutes($fotoTime) > 5) {
                return redirect()->back()->with('error', 'Foto kadaluarsa! Maksimal 5 menit setelah diambil.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Format tanggal foto tidak valid.');
        }

        // 6. GEOFENCING
        $workArea = WorkArea::select(
            'area_id', 'radius_geofence', 'jam_kerja',
            DB::raw('ST_AsText(koordinat_pusat) as location_str')
        )->first(); 

        if (!$workArea) {
            return redirect()->back()->with('error', 'Konfigurasi lokasi kantor belum diset.');
        }

        $cleanStr = str_replace(['POINT(', ')'], '', $workArea->location_str);
        $parts = explode(' ', $cleanStr);
        $officeLng = (float) $parts[0]; 
        $officeLat = (float) $parts[1]; 

        // Hitung Jarak
        $browserDistance = $this->haversineDistance($request->browser_lat, $request->browser_lng, $officeLat, $officeLng);
        $exifLat = isset($exif['GPSLatitude']) ? $this->gpsDmsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N') : null;
        $exifLng = isset($exif['GPSLongitude']) ? $this->gpsDmsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E') : null;
        
        $photoDistance = null;
        if ($exifLat && $exifLng) {
            $photoDistance = $this->haversineDistance($exifLat, $exifLng, $officeLat, $officeLng);
        }

        $isValidLocation = false;
        $finalLat = $request->browser_lat;
        $finalLng = $request->browser_lng;

        if ($browserDistance <= $workArea->radius_geofence) {
            $isValidLocation = true;
        } elseif ($photoDistance !== null && $photoDistance <= $workArea->radius_geofence) {
            $isValidLocation = true;
            $finalLat = $exifLat;
            $finalLng = $exifLng;
        }

        if (!$isValidLocation) {
            $selisih = round($browserDistance - $workArea->radius_geofence);
            return redirect()->back()->with('error', "Anda berada $selisih m di luar radius kantor.");
        }

        // 7. CEK JAM KERJA & STATUS VALIDASI
        $now = Carbon::now();
        $jamKerjaConfig = $workArea->jam_kerja;
        $hariIni = $now->dayOfWeek; 
        $hariKerja = $jamKerjaConfig['hari_kerja'] ?? [1,2,3,4,5]; 
        
        if (!in_array($hariIni, $hariKerja)) {
            return redirect()->back()->with('error', 'Hari ini bukan jadwal hari kerja.');
        }

        $jamMasukBatas = $jamKerjaConfig['masuk'] ?? '08:00';
        $jamPulangBatas = $jamKerjaConfig['pulang'] ?? '17:00';

        // Default System Logic (Status Otomatis)
        $statusValidasiOtomatis = 'Valid';
        $catatanValidasi = null;

        // =====================================================================
        // PERUBAHAN PENTING: STATUS FINAL SELALU PENDING
        // Agar semua absensi masuk ke dashboard approval manajer
        // =====================================================================
        $statusFinal = 'Pending'; 

        // Logika Cek Keterlambatan (Hanya mempengaruhi Status Otomatis)
        if ($request->type == 'masuk') {
            $jamMasukCarbon = Carbon::createFromTimeString($jamMasukBatas);
            $jamPulangCarbon = Carbon::createFromTimeString($jamPulangBatas);
            $isEntryForTomorrow = false;

            if ($now->greaterThan($jamPulangCarbon)) {
                $jamMasukBesok = $jamMasukCarbon->copy()->addDay();
                $windowBukaBesok = $jamMasukBesok->copy()->subHours(2);
                if ($now->greaterThanOrEqualTo($windowBukaBesok)) {
                    $isEntryForTomorrow = true;
                } else {
                    return redirect()->back()->with('error', 'Absensi Masuk ditolak! Jam kerja telah berakhir.');
                }
            }
            
            if (!$isEntryForTomorrow && $now->lessThan($jamMasukCarbon->copy()->subHours(2))) {
                 return redirect()->back()->with('error', 'Terlalu awal! Absensi belum dibuka.');
            }

            if (!$isEntryForTomorrow && $now->greaterThan($jamMasukCarbon)) {
                $statusValidasiOtomatis = 'Need Review'; 
                $catatanValidasi = 'Terlambat (Otomatis): Absen pukul ' . $now->format('H:i') . ' (Jadwal: ' . $jamMasukBatas . ')';
            }
        }
        
        if ($request->type == 'pulang') {
            $jamPulangCarbon = Carbon::createFromTimeString($jamPulangBatas);
            if ($now->lessThan($jamPulangCarbon)) {
                $statusValidasiOtomatis = 'Need Review';
                $catatanValidasi = 'Pulang Cepat (Otomatis): Absen pukul ' . $now->format('H:i') . ' (Jadwal: ' . $jamPulangBatas . ')';
            }
        }

        // Jika tidak ada catatan otomatis (tepat waktu), beri catatan default
        if (!$catatanValidasi) {
            $catatanValidasi = 'Tepat Waktu - Menunggu Validasi Manajer';
        }

        // 8. SIMPAN DATA ATTENDANCE
        $fileName = $currentUserId . '-' . $now->format('Ymd-His') . '-' . $request->type . '.' . $file->extension();
        $path = $file->storeAs('public/absensi', $fileName);
        $publicPath = Storage::url($path);

        $attendance = Attendance::create([
            'emp_id' => $currentUserId,
            'area_id' => $workArea->area_id,
            'waktu_unggah' => $now,
            'latitude' => $finalLat,
            'longitude' => $finalLng,
            'nama_file_foto' => $publicPath,
            'timestamp_ekstraksi' => $exif['DateTimeOriginal'],
            'type' => $request->type,
            'file_hash' => $fileHash
        ]);

        // 9. SIMPAN VALIDATION (STATUS FINAL = PENDING)
        Validation::create([
            'att_id' => $attendance->att_id,
            'status_validasi_otomatis' => $statusValidasiOtomatis, // Bisa Valid/Need Review (Sistem)
            'status_validasi_final' => $statusFinal,               // Selalu Pending (Manusia)
            'catatan_admin' => $catatanValidasi, 
            'timestamp_validasi' => $now
        ]);

        return redirect()->route('karyawan.dashboard')
            ->with('success', 'Absensi berhasil dicatat! Menunggu validasi Manajer.')
            ->withCookie(cookie('device_owner_id', $currentUserId, 2628000));
    }

    // --- HELPER METHODS ---

    private function detectFace($imagePath)
    {
        try {
            $scriptPath = base_path('app/Python/detect_face.py');
            $pythonCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'python' : 'python3';
            $command = sprintf('%s %s %s 2>&1', $pythonCmd, escapeshellarg($scriptPath), escapeshellarg($imagePath));
            $output = trim(shell_exec($command));
            return $output === 'true';
        } catch (\Exception $e) {
            Log::error("Face Detect Error: " . $e->getMessage());
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