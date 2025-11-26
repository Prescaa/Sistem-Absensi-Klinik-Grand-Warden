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
use Illuminate\Validation\Rule;
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

        // Ambil 5 Izin Terakhir
        $recentLeave = Leave::with('employee')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Gabung dan Urutkan berdasarkan waktu (Terbaru di atas)
        $recentActivities = $recentActivities->concat($recentLeave)->sortByDesc(function($item) {
            return $item->waktu_unggah ?? $item->created_at;
        })->take(6); // Ambil 6 item teratas dari gabungan

        // 3. Grafik Tren (TETAP)
        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d M');
            $count = Attendance::whereDate('waktu_unggah', $date)->where('type', 'masuk')->distinct('emp_id')->count('emp_id');
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
    // --- FUNGSI CRUD ABSENSI ---
    // -----------------------------------------------------------------

    public function showManajemenAbsensi()
    {
        // Ambil data absensi + relasi karyawan & validasi
        $attendances = Attendance::with(['employee', 'validation'])
            ->orderBy('waktu_unggah', 'desc')
            ->get(); // Atau gunakan ->paginate(20) jika data sangat banyak

        // Ambil list karyawan untuk dropdown di Modal Tambah/Edit
        $employees = Employee::orderBy('nama')->get();

        return view('admin.manajemen_absensi', [
            'attendances' => $attendances,
            'employees' => $employees
        ]);
    }

    public function storeAbsensi(Request $request)
    {
        $request->validate([
            'emp_id' => 'required|exists:EMPLOYEE,emp_id',
            'waktu_unggah' => 'required|date',
            'type' => 'required|in:masuk,pulang',
            // Validasi foto opsional
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::beginTransaction();
        try {
            // 1. Handle Upload Foto (Jika ada)
            $fotoPath = null;
            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('public/absensi');
                $fotoPath = str_replace('public/', 'storage/', $path);
            }

            // 2. Simpan Data Absensi
            $attendance = Attendance::create([
                'emp_id' => $request->emp_id,
                'waktu_unggah' => Carbon::parse($request->waktu_unggah),
                'type' => $request->type,
                'latitude' => 0, // 0 menandakan input manual/admin
                'longitude' => 0,
                'nama_file_foto' => $fotoPath ?? 'images/placeholder-absensi.jpg',
            ]);

            // 3. ✅ OTOMATIS VALIDASI (DISETUJUI)
            // Karena Admin yang input, kita anggap ini valid mutlak.

            // Ambil ID Karyawan milik Admin yang sedang login
            $adminEmpId = Auth::user()->employee->emp_id ?? null;

            if ($adminEmpId) {
                Validation::create([
                    'att_id' => $attendance->att_id,
                    'admin_id' => $adminEmpId, // Admin tercatat sebagai validator
                    'status_validasi_otomatis' => 'Valid',
                    'status_validasi_final' => 'Valid', // Langsung Valid
                    'catatan_admin' => 'Ditambahkan secara manual oleh Admin (Auto-Approve).',
                    'timestamp_validasi' => now(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data absensi berhasil ditambahkan dan otomatis disetujui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function updateAbsensi(Request $request, $id)
    {
        $att = Attendance::findOrFail($id);

        $request->validate([
            'waktu_unggah' => 'required|date',
            'type' => 'required|in:masuk,pulang',
            'foto' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $att->waktu_unggah = Carbon::parse($request->waktu_unggah);
            $att->type = $request->type;

            if ($request->hasFile('foto')) {
                // Hapus foto lama jika bukan placeholder/default
                if ($att->nama_file_foto && file_exists(public_path($att->nama_file_foto)) && !str_contains($att->nama_file_foto, 'placeholder')) {
                   // unlink(public_path($att->nama_file_foto)); // Opsional: Hapus file fisik
                }

                $path = $request->file('foto')->store('public/absensi');
                $att->nama_file_foto = str_replace('public/', 'storage/', $path);
            }

            $att->save();

            DB::commit();
            return redirect()->back()->with('success', 'Data absensi berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    public function destroyAbsensi($id)
    {
        $att = Attendance::findOrFail($id);

        DB::beginTransaction();
        try {
            // Hapus data validasi terkait dulu (jika ada relasi cascade di DB, ini otomatis. Jika tidak, manual)
            if ($att->validation) {
                $att->validation->delete();
            }

            // Hapus file foto
            if ($att->nama_file_foto && file_exists(public_path($att->nama_file_foto))) {
                // unlink(public_path($att->nama_file_foto));
            }

            $att->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Data absensi berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }
    // -----------------------------------------------------------------
    // --- FUNGSI CRUD KARYAWAN ---
    // -----------------------------------------------------------------

    public function showManajemenIzin()
    {
        $leaves = Leave::with('employee')
            ->orderBy('created_at', 'desc')
            ->get();

        $employees = Employee::orderBy('nama')->get();

        return view('admin.manajemen_izin', [
            'leaves' => $leaves,
            'employees' => $employees
        ]);
    }

    // --- FITUR PENGAJUAN IZIN ADMIN ---
    public function storeIzin(Request $request)
    {
        // Validasi Input
        $request->validate([
            'tipe_izin' => 'required|in:sakit,izin,cuti',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'deskripsi' => 'required|string|max:500',
            'file_bukti' => 'required_if:tipe_izin,sakit|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'file_bukti.required_if' => 'Wajib mengunggah bukti surat sakit jika mengajukan tipe Sakit.'
        ]);

        // Ambil ID Karyawan dari User yang Login
        $user = Auth::user();
        if (!$user->employee) {
            return redirect()->back()->with('error', 'Data karyawan tidak ditemukan. Hubungi IT.');
        }
        $empId = $user->employee->emp_id;

        // Cek Izin Ganda (Overlap)
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

        // Proses Upload File Bukti
        $filePath = null;
        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $fileName = $empId . '-izin-' . now()->format('YmdHis') . '.' . $file->extension();
            $path = $file->storeAs('bukti_izin', $fileName, 'public');
            $filePath = Storage::url($path);
        }

        // Simpan ke Database
        Leave::create([
            'emp_id' => $empId,
            'tipe_izin' => $request->tipe_izin,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'deskripsi' => $request->deskripsi,
            'file_bukti' => $filePath,
            'status' => 'pending' // Default status
        ]);

        // Redirect Kembali ke Halaman Izin Admin
        return redirect()->route('admin.izin.show')->with('success', 'Pengajuan izin berhasil dikirim.');
    }

    public function updateIzin(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $request->validate([
            'tipe_izin' => 'required|in:sakit,izin,cuti',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:pending,disetujui,ditolak',
            'catatan_admin' => 'nullable|string',
            'file_bukti' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $leave->tipe_izin = $request->tipe_izin;
            $leave->tanggal_mulai = $request->tanggal_mulai;
            $leave->tanggal_selesai = $request->tanggal_selesai;
            $leave->deskripsi = $request->deskripsi;
            $leave->status = $request->status;
            $leave->catatan_admin = $request->catatan_admin;

            if ($request->hasFile('file_bukti')) {
                // Hapus file lama jika ada
                /* if ($leave->file_bukti && file_exists(public_path($leave->file_bukti))) {
                    unlink(public_path($leave->file_bukti));
                } */

                $path = $request->file('file_bukti')->store('public/bukti_izin');
                $leave->file_bukti = str_replace('public/', 'storage/', $path);
            }

            $leave->save();

            DB::commit();
            return redirect()->back()->with('success', 'Data izin berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    public function destroyIzin($id)
    {
        $leave = Leave::findOrFail($id);
        DB::beginTransaction();
        try {
            // Hapus file bukti jika ada
            /* if ($leave->file_bukti && file_exists(public_path($leave->file_bukti))) {
                unlink(public_path($leave->file_bukti));
            } */

            $leave->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Data izin berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

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

    // 1. HALAMAN UNGGAH
    public function showUnggah()
    {
        $user = auth()->user();
        if (!$user->employee) return back()->with('error', 'Akun Admin ini belum terhubung ke data Karyawan.');

        $today = Carbon::today();
        $absensiMasuk = Attendance::where('emp_id', $user->employee->emp_id)->whereDate('waktu_unggah', $today)->where('type', 'masuk')->first();
        $absensiPulang = Attendance::where('emp_id', $user->employee->emp_id)->whereDate('waktu_unggah', $today)->where('type', 'pulang')->first();
        
        $workArea = WorkArea::select('radius_geofence', DB::raw('ST_X(koordinat_pusat) as latitude'), DB::raw('ST_Y(koordinat_pusat) as longitude'))->first();

        return view('admin.absensi.unggah', compact('absensiMasuk', 'absensiPulang', 'workArea'));
    }

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

        return redirect()->route('admin.absensi.riwayat')
            ->with('success', 'Absensi berhasil dicatat!')
            ->withCookie(cookie('device_owner_id', $currentUserId, 2628000));
    }

    // 3. HALAMAN RIWAYAT
public function showRiwayat()
    {
        $user = auth()->user();
        
        // Pastikan data karyawan ada
        if (!$user->employee) {
            return redirect()->route('admin.dashboard')->with('error', 'Data karyawan tidak ditemukan.');
        }
        
        $karyawan = $user->employee; 

        // 1. Ambil Data Absensi
        $riwayatAbsensi = Attendance::with('validation')
            ->where('emp_id', $karyawan->emp_id)
            ->orderBy('waktu_unggah', 'desc')
            ->get();
        
        // 2. Hitung Statistik
        $izinCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'izin')->where('status', 'disetujui')->count();
        $sakitCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'sakit')->where('status', 'disetujui')->count();
        $cutiCount = Leave::where('emp_id', $karyawan->emp_id)->where('tipe_izin', 'cuti')->where('status', 'disetujui')->count();

        // 3. ✅ [INI YANG KEMARIN HILANG] Ambil Data Riwayat Izin
        $riwayatIzin = Leave::where('emp_id', $karyawan->emp_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Kirim semua variabel ke View (tambahkan 'riwayatIzin')
        return view('admin.absensi.riwayat', compact(
            'riwayatAbsensi', 
            'karyawan', 
            'izinCount', 
            'sakitCount', 
            'cutiCount',
            'riwayatIzin' // <-- Wajib ada!
        ));
    }

    public function showIzin()
    {
        $user = auth()->user();

        // Cek Validasi: Admin harus terdaftar sebagai karyawan dulu
        if (!$user->employee) {
            return redirect()->route('admin.dashboard')->with('error', 'Akun Anda belum terhubung ke data Karyawan. Hubungi IT.');
        }

        // Ambil data riwayat izin saja
        $riwayatIzin = Leave::where('emp_id', $user->employee->emp_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.absensi.izin', compact('riwayatIzin'));
    }
// =========================================================================
    // ⚠️ COPY 3 FUNGSI DI BAWAH INI KE AdminController.php (Paling Bawah)
    // =========================================================================

    /**
     * Helper 1: Deteksi Wajah via Python
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

            // Jika script python mengembalikan "true", berarti wajah terdeteksi
            if ($result === 'true') return true;
            
            return false; // Gagal deteksi

        } catch (\Exception $e) {
            return false; // Error sistem dianggap gagal
        }
    }

    /**
     * Helper 2: Hitung Jarak (Haversine Formula)
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Radius bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Helper 3: Konversi Koordinat EXIF ke Desimal
     */
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

    // --- TAMBAHKAN INI DI AdminController ---
    public function checkExif(Request $request)
    {
        // Validasi File
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'foto_absensi' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'File terlalu besar (>10MB) atau bukan gambar.'], 422);
        }

        $file = $request->file('foto_absensi');
        $fileHash = md5_file($file->getRealPath());
        
        // Cek Duplikasi
        if (Attendance::where('file_hash', $fileHash)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Foto ini sudah pernah dipakai sebelumnya.'], 400);
        }

        // Cek EXIF (Opsional jika server mendukung)
        if (!function_exists('exif_read_data')) {
            return response()->json(['status' => 'success', 'message' => 'Warning: EXIF Server non-aktif, validasi dilewati.']);
        }

        $exif = @exif_read_data($file->getRealPath());

        // Pastikan ada GPS
        if (!$exif || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Lokasi (GPS) tidak ditemukan pada foto. Pastikan GPS kamera aktif.'
            ], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'Foto Valid.']);
    }
} 