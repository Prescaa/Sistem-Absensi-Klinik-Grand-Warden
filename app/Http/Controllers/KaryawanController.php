<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkArea;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KaryawanController extends Controller
{
    /**
     * Helper function untuk mengambil data absensi hari ini.
     * Ini untuk menghindari duplikasi kode di dashboard() dan unggah().
     */
    private function getTodayAttendance()
    {
        $karyawanId = auth()->user()->employee->emp_id;
        $today = Carbon::today();

        // --- PERBAIKAN QUERY ---
        // Menggunakan kolom 'type' di database, BUKAN 'nama_file_foto LIKE'.
        // Ini jauh lebih baik dan lebih cepat.

        // Cek absensi MASUK hari ini
        $absensiMasuk = Attendance::where('emp_id', $karyawanId)
            ->whereDate('waktu_unggah', $today)
            ->where('type', 'masuk') // <-- Query diperbaiki
            ->first();

        // Cek absensi PULANG hari ini
        $absensiPulang = Attendance::where('emp_id', $karyawanId)
            ->whereDate('waktu_unggah', $today)
            ->where('type', 'pulang') // <-- Query diperbaiki
            ->first();

        return [
            'absensiMasuk' => $absensiMasuk,
            'absensiPulang' => $absensiPulang
        ];
    }

    // Fungsi untuk menampilkan Dashboard Karyawan
    public function dashboard()
    {
        // Panggil helper function
        $data = $this->getTodayAttendance();

        // Kirim DUA variabel ini ke view
        return view('karyawan.dashboard', $data);
    }

    // Fungsi untuk menampilkan Halaman Unggah
    public function unggah()
    {
        // --- PERBAIKAN ERROR UNDEFINED VARIABLE ---
        // Panggil helper function yang sama dengan dashboard
        $data = $this->getTodayAttendance();

        // Kirim DUA variabel ini ke view
        return view('karyawan.unggah', $data);
    }

    // Fungsi untuk menampilkan Halaman Riwayat
    public function riwayat()
    {
        return view('karyawan.riwayat');
    }

    // Fungsi untuk menampilkan Halaman Izin
    public function izin()
    {
        return view('karyawan.izin');
    }

    // Fungsi untuk menampilkan Halaman Profil
    public function profil()
    {
        return view('karyawan.profil');
    }

    /**
     * CATATAN: Method ini sekarang fungsinya SAMA PERSIS dengan method unggah().
     * Sebaiknya Anda hapus method ini dan perbarui file routes/web.php Anda
     * agar hanya menggunakan method unggah() saja.
     */
    public function showUploadForm(Request $request, $type)
    {
        if (!in_array($type, ['masuk', 'pulang'])) {
            abort(404, 'Tipe absensi tidak valid.');
        }

        // Memanggil method unggah() agar logikanya sama
        return $this->unggah();
    }


    /**
     * Menyimpan foto absensi yang diunggah oleh karyawan.
     */
    public function storeFoto(Request $request)
    {
        // 1. Validasi (Sama seperti sebelumnya)
        $request->validate([
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:7000',
            'type' => 'required|string|in:masuk,pulang',
        ]);

        $file = $request->file('foto_absensi');

        // 2. Validasi EXIF dan Lokasi (Logika ini SAMA seperti sebelumnya)
        $exif = @exif_read_data($file->getRealPath());
        if (empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            return redirect()->back()->with('error', 'Foto tidak memiliki data GPS EXIF.');
        }
        $photoLat = $this->gpsDmsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
        $photoLon = $this->gpsDmsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef']);

        $workArea = WorkArea::select('area_id', 'radius_geofence', DB::raw('ST_X(koordinat_pusat) as latitude'), DB::raw('ST_Y(koordinat_pusat) as longitude'))->find(1);
        if (!$workArea) { return redirect()->back()->with('error', 'Area kerja belum diatur.'); }

        $distance = $this->haversineDistance($photoLat, $photoLon, $workArea->latitude, $workArea->longitude);
        if ($distance > $workArea->radius_geofence) {
            return redirect()->back()->with('error', 'Anda berada di luar radius absensi. Jarak: '.round($distance).'m.');
        }
        // --- Akhir Validasi Lokasi ---


        // 3. Buat Nama File (Sama seperti sebelumnya)
        $karyawanId = auth()->user()->employee->emp_id;
        $fileName = $karyawanId . '-' . now()->format('Ymd-His') . '-' . $request->type . '.' . $file->extension();

        // 4. Simpan File
        $path = $file->storeAs('public/absensi', $fileName);
        $publicPath = Storage::url($path);

        // 5. BUAT BARIS BARU DI DATABASE
        // --- PERBAIKAN PENTING ---
        Attendance::create([
            'emp_id' => $karyawanId,
            'area_id' => $workArea->area_id,
            'waktu_unggah' => now(),
            'latitude' => $photoLat,
            'longitude' => $photoLon,
            'nama_file_foto' => $publicPath,
            'timestamp_ekstraksi' => $exif['DateTimeOriginal'] ?? null,
            'type' => $request->type // <-- BARIS INI DITAMBAHKAN
        ]);

        return redirect()->route('karyawan.dashboard')
                         ->with('success', 'Absensi ' . $request->type . ' berhasil divalidasi dan disimpan.');
    }


    // --- DUA FUNGSI HELPER DI BAWAH INI (TIDAK BERUBAH) ---

    /**
     * Menghitung jarak antara dua titik koordinat di bumi.
     * Menggunakan Haversine Formula.
     * @return float Jarak dalam METER.
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance; // dalam meter
    }

    /**
     * Mengonversi format GPS EXIF (DMS) menjadi Decimal Degrees (DD).
     * @param array $dmsArray Array dari EXIF (cth: ["5/1", "58/1", "46/1"])
     * @param string $ref Referensi (N, S, E, atau W)
     * @return float Koordinat dalam Decimal Degrees
     */
    private function gpsDmsToDecimal($dmsArray, $ref)
    {
        // Fungsi helper untuk membagi string "angka/pembagi"
        $evalCoordPart = function ($coordPart) {
            $parts = explode('/', $coordPart);
            if (count($parts) == 2) {
                // Pastikan pembagi tidak nol
                return $parts[1] == 0 ? 0 : $parts[0] / $parts[1];
            }
            return (float)$parts[0];
        };

        $degrees = $evalCoordPart($dmsArray[0]);
        $minutes = $evalCoordPart($dmsArray[1]);
        $seconds = $evalCoordPart($dmsArray[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        // Jika referensi 'S' (Selatan) atau 'W' (Barat), nilainya negatif
        if ($ref == 'S' || $ref == 'W') {
            return -$decimal;
        }

        return $decimal;
    }
}
