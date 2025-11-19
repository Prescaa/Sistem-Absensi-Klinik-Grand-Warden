<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkArea;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Leave;

class KaryawanController extends Controller
{
    private function getTodayAttendance()
    {
        $karyawanId = auth()->user()->employee->emp_id;
        $today = Carbon::today();

        $absensiMasuk = Attendance::where('emp_id', $karyawanId)->whereDate('waktu_unggah', $today)->where('type', 'masuk')->first();
        $absensiPulang = Attendance::where('emp_id', $karyawanId)->whereDate('waktu_unggah', $today)->where('type', 'pulang')->first();
        
        // Cek apakah hari ini ada izin yang DISETUJUI
        $todayLeave = Leave::where('emp_id', $karyawanId)
            ->where('status', 'disetujui')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->first();
            
        return [
            'absensiMasuk' => $absensiMasuk, 
            'absensiPulang' => $absensiPulang,
            'todayLeave' => $todayLeave // <-- PENTING: Kirim data izin ke view
        ];
    }

    public function dashboard() { return view('karyawan.dashboard', $this->getTodayAttendance()); }
    
    public function unggah() { return view('karyawan.unggah', $this->getTodayAttendance()); }
    
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
        $request->validate(['foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:7000', 'type' => 'required|string|in:masuk,pulang']);
        $file = $request->file('foto_absensi');
        $exif = @exif_read_data($file->getRealPath());

        if (empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            return redirect()->back()->with('error', 'Foto tidak memiliki data GPS EXIF.');
        }

        $photoLat = $this->gpsDmsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
        $photoLon = $this->gpsDmsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
        
        $workArea = WorkArea::select('area_id', 'radius_geofence', DB::raw('ST_X(koordinat_pusat) as latitude'), DB::raw('ST_Y(koordinat_pusat) as longitude'))->find(1);
        if (!$workArea) return redirect()->back()->with('error', 'Area kerja belum diatur.');

        $distance = $this->haversineDistance($photoLat, $photoLon, $workArea->latitude, $workArea->longitude);
        if ($distance > $workArea->radius_geofence) {
            return redirect()->back()->with('error', 'Anda berada di luar radius absensi. Jarak: '.round($distance).'m.');
        }

        $karyawanId = auth()->user()->employee->emp_id;
        $fileName = $karyawanId . '-' . now()->format('Ymd-His') . '-' . $request->type . '.' . $file->extension();
        $path = $file->storeAs('public/absensi', $fileName);
        $publicPath = Storage::url($path);

        Attendance::create([
            'emp_id' => $karyawanId, 'area_id' => $workArea->area_id, 'waktu_unggah' => now(), 'latitude' => $photoLat, 'longitude' => $photoLon, 'nama_file_foto' => $publicPath, 'timestamp_ekstraksi' => $exif['DateTimeOriginal'] ?? null, 'type' => $request->type
        ]);

        return redirect()->route('karyawan.dashboard')->with('success', 'Absensi ' . $request->type . ' berhasil disimpan.');
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