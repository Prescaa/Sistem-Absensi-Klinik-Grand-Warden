<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Models\Validation;
use App\Models\WorkArea;
use App\Models\Leave;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Menampilkan halaman dashboard admin.
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

        // 2. Data untuk "Menunggu Validasi Terbaru"
        $recentActivities = Attendance::whereDoesntHave('validation')
            ->with('employee')
            ->orderBy('waktu_unggah', 'desc')
            ->take(5)
            ->get();

        // 3. --- FITUR BARU: ANALISIS TREN KEHADIRAN (7 HARI TERAKHIR) ---
        $chartLabels = [];
        $chartData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d M'); // Label Tanggal (misal: 20 Nov)

            // Hitung jumlah karyawan yang hadir (masuk) pada tanggal tersebut
            $count = Attendance::whereDate('waktu_unggah', $date)
                ->where('type', 'masuk')
                ->distinct('emp_id')
                ->count('emp_id');

            $chartData[] = $count;
        }

        return view('admin.dashboard', [
            'totalEmployees' => $totalEmployees,
            'presentCount' => $presentCount,
            'izinCount' => $izinCount,
            'sakitCount' => $sakitCount,
            'recentActivities' => $recentActivities,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData
        ]);
    }

    // -----------------------------------------------------------------
    // --- FUNGSI CRUD KARYAWAN ---
    // -----------------------------------------------------------------

    public function showManajemenKaryawan()
    {
        // Ambil data karyawan beserta relasi user
        $employee = Employee::with('user')->get();
        return view('admin.manajemen_karyawan', ['employee' => $employee]);
    }

    public function storeKaryawan(Request $request)
    {
        $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:50', 'unique:employee'],
            'departemen' => ['nullable', 'string', 'max:100'],
            'posisi' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:user'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:Karyawan,Admin,Manajemen'],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            // ✅ VALIDASI FOTO BARU
            'foto_profil' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        DB::beginTransaction();
        try {
            // 1. Buat User
            $user = User::create([
                'username' => $request->username,
                'email' => $request->username . '@klinik.com',
                'password_hash' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            // 2. Handle Upload Foto (Jika ada)
            $fotoPath = null;
            if ($request->hasFile('foto_profil')) {
                // Simpan di folder: public/storage/photos
                $path = $request->file('foto_profil')->store('public/photos');
                // Ubah path agar bisa diakses via asset(): public/photos/namafile.jpg -> storage/photos/namafile.jpg
                $fotoPath = str_replace('public/', 'storage/', $path);
            }

            // 3. Buat Employee
            Employee::create([
                'user_id' => $user->user_id,
                'nama' => $request->nama,
                'nip' => $request->nip,
                'departemen' => $request->departemen,
                'posisi' => $request->posisi,
                'no_telepon' => $request->no_telepon,
                'alamat' => $request->alamat,
                'foto_profil' => $fotoPath, // ✅ SIMPAN PATH FOTO
                'status_aktif' => true,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'User baru berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateKaryawan(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $employee = $user->employee;

        $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:50', 'unique:employee,nip,' . $employee->emp_id . ',emp_id'],
            'departemen' => ['nullable', 'string', 'max:100'],
            'posisi' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:user,username,' . $user->user_id . ',user_id'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:Karyawan,Admin,Manajemen'],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            // ✅ VALIDASI FOTO UPDATE
            'foto_profil' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        DB::beginTransaction();
        try {
            // Update User
            $user->username = $request->username;
            $user->role = $request->role;
            if ($request->filled('password')) {
                $user->password_hash = Hash::make($request->password);
            }
            $user->save();

            // Handle Upload Foto Baru
            if ($request->hasFile('foto_profil')) {
                // (Opsional) Hapus foto lama jika ada & bukan default
                if ($employee->foto_profil && file_exists(public_path($employee->foto_profil))) {
                    // Logika hapus file lama bisa ditambahkan di sini jika perlu hemat storage
                    // unlink(public_path($employee->foto_profil));
                }

                $path = $request->file('foto_profil')->store('public/photos');
                $employee->foto_profil = str_replace('public/', 'storage/', $path);
            }

            // Update Employee
            $employee->nama = $request->nama;
            $employee->nip = $request->nip;
            $employee->departemen = $request->departemen;
            $employee->posisi = $request->posisi;
            $employee->no_telepon = $request->no_telepon;
            $employee->alamat = $request->alamat;
            $employee->save();

            DB::commit();
            return redirect()->back()->with('success', 'Data pengguna berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    public function destroyKaryawan($id)
    {
        if ($id == Auth::user()->user_id) {
            return redirect()->back()->with('error', 'Tindakan Ditolak: Anda tidak dapat menghapus akun sendiri.');
        }

        $user = User::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($user->employee) {
                $user->employee->delete();
            }
            $user->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Pengguna berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // --- FUNGSI LAPORAN & GEOFENCING ---
    // -----------------------------------------------------------------



    public function exportLaporan(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();
        $filename = "Laporan-Absensi_" . $startDate->format('Ymd') . "-" . $endDate->format('Ymd') . ".csv";

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

            // Helper Sanitasi untuk mencegah CSV Injection
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

                // Hitung Izin/Sakit (Disetujui)
                $totalIzinSakit = Leave::where('emp_id', $karyawan->emp_id)
                    ->where('status', 'disetujui')
                    ->where(function($q) use ($startDate, $endDate) {
                        $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_selesai', [$startDate, $endDate]);
                    })->count();

                $totalHariKerja = $this->countWorkingDaysInRange($startDate, $endDate);
                $persentase = $totalHariKerja > 0 ? ($totalHadir / $totalHariKerja) * 100 : 0;

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

    private function countWorkingDaysInRange($startDate, $endDate)
    {
        $count = 0;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if (!$current->isWeekend()) {
                $count++;
            }
            $current->addDay();
        }
        return $count;
    }

    public function showGeofencing()
    {
        $lokasi = WorkArea::select(
            'area_id', 'nama_area', 'radius_geofence', 'jam_kerja',
            DB::raw('ST_X(koordinat_pusat) as latitude'),
            DB::raw('ST_Y(koordinat_pusat) as longitude')
        )->where('area_id', 1)->first();

        return view('admin.geofencing', ['lokasi' => $lokasi]);
    }

    public function saveGeofencing(Request $request)
    {
        $request->validate([
            'nama_area' => 'required|string|max:100',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric|min:50',
        ]);

        $lokasi = WorkArea::firstOrNew(['area_id' => 1]);
        $lokasi->nama_area = $request->nama_area;
        $lokasi->radius_geofence = $request->radius;
        $lat = (float) $request->latitude;
        $lon = (float) $request->longitude;
        $lokasi->koordinat_pusat = DB::raw("POINT($lat, $lon)");
        $lokasi->save();

        return redirect()->route('admin.geofencing.show')->with('success', 'Lokasi berhasil diperbarui!');
    }

}
