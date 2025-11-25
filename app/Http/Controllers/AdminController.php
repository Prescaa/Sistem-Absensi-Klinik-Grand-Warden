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

    /**
     * Menampilkan halaman validasi absensi.
     */


    public function handleApprove(Request $request)
    {
        return redirect()->back()->with('success', 'Absensi telah disetujui.');
    }

    public function handleReject(Request $request)
    {
        return redirect()->back()->with('success', 'Absensi telah ditolak.');
    }

    // -----------------------------------------------------------------
    // --- FUNGSI CRUD KARYAWAN ---
    // -----------------------------------------------------------------

    public function showManajemenKaryawan()
    {
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
            // ✅ UPDATE: Tambahkan Validasi Role
            'role' => ['required', 'in:Karyawan,Admin,Manajemen'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'username' => $request->username,
                'email' => $request->username . '@klinik.com',
                'password_hash' => Hash::make($request->password),
                // ✅ UPDATE: Gunakan role dari input, bukan hardcode
                'role' => $request->role,
            ]);

            Employee::create([
                'user_id' => $user->user_id,
                'nama' => $request->nama,
                'nip' => $request->nip,
                'departemen' => $request->departemen,
                'posisi' => $request->posisi,
                'status_aktif' => true,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'User baru berhasil ditambahkan sebagai ' . $request->role . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
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
            // ✅ UPDATE: Validasi Role saat update
            'role' => ['required', 'in:Karyawan,Admin,Manajemen'],
        ]);

        DB::beginTransaction();
        try {
            $user->username = $request->username;
            // ✅ UPDATE: Simpan perubahan Role
            $user->role = $request->role;

            if ($request->filled('password')) {
                $user->password_hash = Hash::make($request->password);
            }
            $user->save();

            $employee->nama = $request->nama;
            $employee->nip = $request->nip;
            $employee->departemen = $request->departemen;
            $employee->posisi = $request->posisi;
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
        // Cek apakah user mencoba menghapus diri sendiri
        if ($id == Auth::user()->user_id) {
            return redirect()->back()->with('error', 'Tindakan Ditolak: Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
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

    public function showLaporan()
    {
        return view('admin.laporan');
    }

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
