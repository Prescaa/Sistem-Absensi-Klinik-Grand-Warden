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

class KaryawanController extends Controller
{
    private function getTodayAttendance() {
        $karyawanId = auth()->user()->employee->emp_id;
        $today = Carbon::today();

        // --- LOGIKA ABSEN ULANG (PERBAIKAN UTAMA) ---
        // Kita cari absensi hari ini, TAPI kita filter:
        // Ambil yang status validasinya BUKAN 'Invalid' atau 'Rejected'.
        // Jika statusnya 'Pending' atau 'Valid', maka dianggap sudah absen.
        // Jika statusnya 'Invalid' (Ditolak), query ini TIDAK akan mengambil data tersebut,
        // sehingga variabel $absensiMasuk/Pulang akan null, dan tombol absen muncul lagi.

        $absensiMasuk = Attendance::where('emp_id', $karyawanId)
            ->whereDate('waktu_unggah', $today)
            ->where('type', 'masuk')
            ->whereDoesntHave('validation', function($q) {
                $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
            })
            ->latest('waktu_unggah') // Ambil yang paling baru jika ada duplikat
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
    public function dashboard() { return view('karyawan.dashboard', $this->getTodayAttendance()); }

    // Di app/Http/Controllers/KaryawanController.php

    public function unggah() {
        $data = $this->getTodayAttendance();

        // --- TAMBAHAN: Ambil data WorkArea untuk validasi Frontend ---
        // Kita butuh Latitude, Longitude, dan Radius untuk dicek oleh JavaScript browser
        $data['workArea'] = WorkArea::select(
                'radius_geofence',
                DB::raw('ST_X(koordinat_pusat) as latitude'),
                DB::raw('ST_Y(koordinat_pusat) as longitude')
            )->find(1); // Asumsi ID area = 1

        return view('karyawan.unggah', $data);
    }

    public function showUploadForm(Request $request, $type) {
        if (!in_array($type, ['masuk', 'pulang'])) { abort(404); }
        return $this->unggah();
    }

    public function riwayat()
    {
        $karyawan = auth()->user()->employee;
        $riwayatAbsensi = Attendance::with('validation')->where('emp_id', $karyawan->emp_id)->orderBy('waktu_unggah', 'desc')->get();

        $izinCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'izin')->where('status', 'disetujui')->count();
        $sakitCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'sakit')->where('status', 'disetujui')->count();
        $cutiCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'cuti')->where('status', 'disetujui')->count();

        return view('karyawan.riwayat', compact('karyawan', 'riwayatAbsensi', 'izinCount', 'sakitCount', 'cutiCount'));
    }

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
            'file_bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $filePath = null;
        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $fileName = auth()->user()->employee->emp_id . '-izin-' . now()->format('YmdHis') . '.' . $file->extension();
            $path = $file->storeAs('bukti_izin', $fileName, 'public');
            $filePath = Storage::url($path);
        }

        Leave::create([
            'emp_id' => auth()->user()->employee->emp_id,
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
            'alamat' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:20',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

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


    // 1. Validasi Input (Termasuk koordinat dari Browser)
    $request->validate([
        'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:7000',
        'type' => 'required|string|in:masuk,pulang',
        'browser_lat' => 'required|numeric', // Wajib ada
        'browser_lng' => 'required|numeric', // Wajib ada
    ]);

    $file = $request->file('foto_absensi');
    $browserLat = (float) $request->browser_lat;
    $browserLng = (float) $request->browser_lng;

    // --- 2. CEK HASH FILE (Anti-Duplikasi) ---
    $fileHash = md5_file($file->getRealPath());
    if (Attendance::where('file_hash', $fileHash)->exists()) {
        return redirect()->back()->with('error', 'Foto ini sudah pernah digunakan sebelumnya.');
    }

    // --- 3. BACA EXIF FOTO (Bukti Lokasi) ---
    $exif = @exif_read_data($file->getRealPath());
    if (!$exif || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
        return redirect()->back()->with('error', 'Validasi Gagal: Foto tidak memiliki data GPS. Pastikan menggunakan kamera langsung dengan GPS aktif.');
    }

    // Cek Tanggal Foto (Anti-Foto Lama)
    if (empty($exif['DateTimeOriginal'])) {
        return redirect()->back()->with('error', 'Tanggal foto tidak terdeteksi.');
    }
    // (Opsional: Tambahkan validasi selisih waktu menit di sini seperti kode lama Anda)

    // Konversi Koordinat EXIF
    $exifLat = $this->gpsDmsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
    $exifLng = $this->gpsDmsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef']);

    // --- 4. AMBIL DATA KANTOR ---
    // --- 4. AMBIL DATA KANTOR ---
// Kita harus men-select secara manual menggunakan fungsi ST_X dan ST_Y
$workArea = WorkArea::select(
    'area_id',
    'radius_geofence',
    \Illuminate\Support\Facades\DB::raw('ST_X(koordinat_pusat) as latitude'),
    \Illuminate\Support\Facades\DB::raw('ST_Y(koordinat_pusat) as longitude')
)->find(1);

if (!$workArea) {
    return redirect()->back()->with('error', 'Data lokasi kantor tidak ditemukan.');
}

    // --- 5. LOGIKA VALIDASI GANDA (DOUBLE CHECK) ---

    // A. Cek Jarak Browser vs Kantor (Real-time Check)
    $distBrowser = $this->haversineDistance($browserLat, $browserLng, $workArea->latitude, $workArea->longitude);
    if ($distBrowser > $workArea->radius_geofence) {
        return redirect()->back()->with('error', "Posisi Anda (Browser) terdeteksi diluar jangkauan ($distBrowser m).");
    }

    // B. Cek Jarak Foto vs Kantor (Evidence Check)
    $distExif = $this->haversineDistance($exifLat, $exifLng, $workArea->latitude, $workArea->longitude);
    if ($distExif > $workArea->radius_geofence) {
        return redirect()->back()->with('error', "Foto terdeteksi diambil diluar area kantor ($distExif m).");
    }

    // C. CROSS-CHECK: Konsistensi Browser vs Foto
    // Apakah HP dan Foto berada di tempat yang sama? (Toleransi 100m-200m untuk akurasi GPS)
    $distConsistency = $this->haversineDistance($browserLat, $browserLng, $exifLat, $exifLng);
    if ($distConsistency > 1000) {
        return redirect()->back()->with('error', "KEAMANAN: Lokasi HP dan Lokasi Foto tidak cocok! Selisih: " . round($distConsistency) . "m. Jangan gunakan Fake GPS atau foto lama.");
    }

    // --- 6. SIMPAN DATA (Jika Lolos Semua) ---
    $karyawanId = auth()->user()->employee->emp_id;
    $fileName = $karyawanId . '-' . now()->format('Ymd-His') . '-' . $request->type . '.' . $file->extension();
    $path = $file->storeAs('public/absensi', $fileName);
    $publicPath = Storage::url($path);

    Attendance::create([
        'emp_id' => $karyawanId,
        'area_id' => $workArea->area_id,
        'waktu_unggah' => now(),
        'latitude' => $exifLat,  // Kita simpan yang EXIF sebagai bukti fisik
        'longitude' => $exifLng,
        'nama_file_foto' => $publicPath,
        'timestamp_ekstraksi' => $exif['DateTimeOriginal'],
        'type' => $request->type,
        'file_hash' => $fileHash
    ]);

    return redirect()->route('karyawan.dashboard')->with('success', 'Absensi berhasil! Lokasi terverifikasi.');
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
