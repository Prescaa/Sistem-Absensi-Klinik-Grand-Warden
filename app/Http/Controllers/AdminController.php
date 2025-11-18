<?php

namespace App\Http\Controllers;

// --- SEMUA USE STATEMENT DIKUMPULKAN DI SINI ---
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

// --- HANYA ADA SATU DEKLARASI CLASS ---
class AdminController extends Controller
{
    /**
     * Menampilkan halaman dashboard admin.
     */
    /**
     * Menampilkan halaman dashboard admin.
     */
    public function dashboard()
    {
        $today = Carbon::today();

        // 1. Total Karyawan
        $totalEmployees = Employee::count();

        // 2. Hadir Hari Ini (Berdasarkan type 'masuk')
        $presentCount = Attendance::whereDate('waktu_unggah', $today)
            ->where('type', 'masuk')
            ->distinct('emp_id') // Agar tidak terhitung ganda jika upload ulang
            ->count('emp_id');

        // 3. Data Absensi Terbaru (Pending / Belum Divalidasi)
        // Mengambil 5 data terakhir yang belum ada di tabel validation
        $recentActivities = Attendance::whereDoesntHave('validation')
            ->with('employee')
            ->orderBy('waktu_unggah', 'desc')
            ->take(5)
            ->get();

        // (Opsional) Hitung Izin/Sakit jika nanti Anda sudah punya tabelnya
        // Untuk sementara saya set 0 agar tidak error di view
        $izinCount = 0;
        $sakitCount = 0;

        return view('admin.dashboard', [
            'totalEmployees' => $totalEmployees,
            'presentCount' => $presentCount,
            'izinCount' => $izinCount,
            'sakitCount' => $sakitCount,
            'recentActivities' => $recentActivities
        ]);
    }

    /**
     * Menampilkan halaman validasi absensi.
     */
    public function showValidasiPage()
    {
        // 1. Ambil Absensi Pending (Yang sudah ada sebelumnya)
        $pendingAttendances = Attendance::whereDoesntHave('validation')
            ->with('employee')
            ->orderBy('waktu_unggah', 'desc')
            ->get();

        // 2. Ambil Pengajuan Izin Pending (BARU)
        $pendingLeaves = Leave::with('employee')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc') // Urutkan dari yang terlama diajukan
            ->get();

        // Kirim kedua variabel ke view
        return view('admin.validasi', [
            'attendances' => $pendingAttendances,
            'leaves' => $pendingLeaves
        ]);
    }

    /**
     * Menangani proses approve absensi.
     */
    public function handleApprove(Request $request)
    {
        // TODO: Logika untuk approve absensi
        // $validation = Validation::find($request->input('validation_id'));
        // $validation->update(['status_validasi_final' => 'approved']);
        return redirect()->back()->with('success', 'Absensi telah disetujui.');
    }

    /**
     * Menangani proses reject absensi.
     */
    public function handleReject(Request $request)
    {
        // TODO: Logika untuk reject absensi
        // $validation = Validation::find($request->input('validation_id'));
        // $validation->update(['status_validasi_final' => 'rejected', 'catatan_admin' => $request->input('catatan')]);
        return redirect()->back()->with('success', 'Absensi telah ditolak.');
    }

    // -----------------------------------------------------------------
    // --- FUNGSI CRUD KARYAWAN ---
    // -----------------------------------------------------------------

    /**
     * READ: Menampilkan halaman manajemen karyawan
     */
    public function showManajemenKaryawan()
    {
        // Ambil semua data karyawan beserta data user login-nya
        $employee = Employee::with('user')->get();

        // Kirim data ke view
        return view('admin.manajemen_karyawan', ['employee' => $employee]);
    }

    /**
     * CREATE: Menangani proses penambahan karyawan baru.
     */
    public function storeKaryawan(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:50', 'unique:employee'],
            'departemen' => ['nullable', 'string', 'max:100'],
            'posisi' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:user'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 2. Gunakan Transaksi Database
        DB::beginTransaction();
        try {
            // 3. Buat User baru
            $user = User::create([
                'username' => $request->username,
                'email' => $request->username . '@klinik.com', // Email default
                'password_hash' => Hash::make($request->password), // Sesuai model User Anda
                'role' => 'Karyawan', // Otomatis set role
            ]);

            // 4. Buat Employee baru yang terhubung dengan User
            Employee::create([
                'user_id' => $user->user_id, // Menggunakan primary key yang benar
                'nama' => $request->nama,
                'nip' => $request->nip,
                'departemen' => $request->departemen,
                'posisi' => $request->posisi,
                'status_aktif' => true, // Otomatis aktif
            ]);

            // 5. Jika sukses, commit transaksi
            DB::commit();

            return redirect()->back()->with('success', 'Karyawan baru berhasil ditambahkan.');

        } catch (\Exception $e) {
            // 6. Jika gagal, batalkan semua
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * UPDATE: Menangani proses update data karyawan.
     */
    public function updateKaryawan(Request $request, $id)
    {
        // $id di sini adalah user_id
        $user = User::findOrFail($id);
        $employee = $user->employee;

        // 1. Validasi input
        $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:50', 'unique:employee,nip,' . $employee->emp_id . ',emp_id'], // Abaikan NIP milik sendiri
            'departemen' => ['nullable', 'string', 'max:100'],
            'posisi' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:user,username,' . $user->user_id . ',user_id'], // Abaikan username milik sendiri
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()], // Password boleh kosong
        ]);

        // 2. Gunakan Transaksi
        DB::beginTransaction();
        try {
            // 3. Update User
            $user->username = $request->username;

            // Hanya update password jika diisi
            if ($request->filled('password')) {
                $user->password_hash = Hash::make($request->password); // Sesuai model User Anda
            }
            $user->save();

            // 4. Update Employee
            $employee->nama = $request->nama;
            $employee->nip = $request->nip;
            $employee->departemen = $request->departemen;
            $employee->posisi = $request->posisi;
            $employee->save();

            // 5. Commit
            DB::commit();

            return redirect()->back()->with('success', 'Data karyawan berhasil diperbarui.');

        } catch (\Exception $e) {
            // 6. Rollback
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * DELETE: Menangani proses hapus karyawan.
     */
    public function destroyKaryawan($id)
    {
        // $id di sini adalah user_id
        $user = User::findOrFail($id);

        // Kita gunakan transaksi untuk memastikan keduanya terhapus
        DB::beginTransaction();
        try {
            // Hapus employee dulu
            if ($user->employee) {
                $user->employee->delete();
            }
            // Hapus user
            $user->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Karyawan berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }

    // --- AKHIR FUNGSI CRUD KARYAWAN ---

    /**
     * Menampilkan halaman laporan.
     */
    public function showLaporan()
    {
        return view('admin.laporan');
    }

    /**
     * Menangani proses ekspor laporan.
     */
    public function exportLaporan(Request $request)
    {
        // 1. Validasi Input Tanggal
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();

        $filename = "Laporan-Absensi_" . $startDate->format('Ymd') . "_sd_" . $endDate->format('Ymd') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // 2. Ambil Semua Karyawan
        $listKaryawan = Employee::orderBy('nama')->get();

        // 3. Buat Callback untuk Streaming CSV
        $callback = function() use ($listKaryawan, $startDate, $endDate) {
            $file = fopen('php://output', 'w');

            // Header CSV
            fputcsv($file, ['NIP', 'Nama Karyawan', 'Total Hadir', 'Total Terlambat', 'Total Izin/Sakit', 'Persentase Kehadiran (%)']);

            foreach ($listKaryawan as $karyawan) {
                // Hitung Kehadiran dalam Rentang Tanggal
                $kehadiran = Attendance::where('emp_id', $karyawan->emp_id)
                    ->whereBetween('waktu_unggah', [$startDate, $endDate])
                    ->where('type', 'masuk')
                    ->get();

                $totalHadir = $kehadiran->count();

                // Hitung Terlambat (> 08:00:00)
                $totalTerlambat = $kehadiran->filter(function ($att) {
                    return $att->waktu_unggah->format('H:i:s') > '08:00:00';
                })->count();

                $totalIzinSakit = 0; // Placeholder

                // Hitung Hari Kerja (Senin-Jumat) dalam Rentang Tanggal
                $totalHariKerja = $this->countWorkingDaysInRange($startDate, $endDate);

                $persentase = $totalHariKerja > 0 ? ($totalHadir / $totalHariKerja) * 100 : 0;

                // Tulis Baris CSV
                fputcsv($file, [
                    $karyawan->nip,
                    $karyawan->nama,
                    $totalHadir,
                    $totalTerlambat,
                    $totalIzinSakit,
                    number_format(min($persentase, 100), 1) // Format 1 desimal
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper untuk menghitung hari kerja dalam rentang tanggal tertentu.
     */
    private function countWorkingDaysInRange($startDate, $endDate)
    {
        $count = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Cek jika bukan Sabtu (6) dan Minggu (0)
            if (!$current->isWeekend()) {
                $count++;
            }
            $current->addDay();
        }
        return $count;
    }

    public function showGeofencing()
    {
        // Ambil data lokasi.
        // Kita juga perlu memilih nilai Lat dan Lon dari kolom POINT
        $lokasi = WorkArea::select(
            'area_id',
            'nama_area',
            'radius_geofence',
            'jam_kerja',
            // Gunakan fungsi ST_X dan ST_Y untuk mengekstrak Lat/Lon dari POINT
            DB::raw('ST_X(koordinat_pusat) as latitude'),
            DB::raw('ST_Y(koordinat_pusat) as longitude')
        )->where('area_id', 1)->first();

        // Kirim data lokasi ke view
        return view('admin.geofencing', ['lokasi' => $lokasi]);
    }

    /**
     * Menyimpan data pengaturan geofencing.
     */
    public function saveGeofencing(Request $request)
    {
        // Validasi input
        $request->validate([
            'nama_area' => 'required|string|max:100', // Validasi untuk nama
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric|min:50', // ini 'radius' dari form
        ]);

        // Cari data lokasi berdasarkan 'area_id' = 1, atau buat baru
        $lokasi = WorkArea::firstOrNew(['area_id' => 1]);

        // === PERBAIKAN UTAMA DI SINI ===

        // 1. Simpan ke 'nama_area', bukan 'latitude'
        $lokasi->nama_area = $request->nama_area;

        // 2. Simpan ke 'radius_geofence', bukan 'radius'
        $lokasi->radius_geofence = $request->radius;

        // 3. Simpan lat/lon ke 'koordinat_pusat'
        // Kolom 'koordinat_pusat' Anda adalah tipe POINT.
        // Cara termudah menanganinya adalah menggunakan DB::raw() untuk
        // membuat fungsi POINT() dari MySQL.
        $lokasi->koordinat_pusat = DB::raw("POINT({$request->latitude}, {$request->longitude})");

        // Catatan: Menyimpan sebagai POINT adalah cara database yang "benar",
        // tetapi mengambilnya kembali untuk ditampilkan di form sedikit lebih rumit.

        $lokasi->save();

        // Kembali ke halaman sebelumnya dengan pesan sukses
        return redirect()->route('admin.geofencing.show')
                         ->with('success', 'Pengaturan lokasi geofencing berhasil diperbarui!');
    }

    /**
     * TAMBAHKAN METODE INI:
     * Menyimpan hasil validasi (Approve/Reject)
     */
    public function submitValidasi(Request $request)
    {
        // Validasi input dari form
        $request->validate([
            'att_id' => 'required|exists:ATTENDANCE,att_id',
            'status_validasi' => 'required|in:Approved,Rejected', // Pastikan nilainya
            'catatan_validasi' => 'nullable|string|max:500'
        ]);

        // Dapatkan 'emp_id' dari admin yang sedang login
        // (Berdasarkan migrasi, 'admin_id' mengacu ke 'emp_id')
        $adminEmpId = Auth::user()->employee->emp_id;

        // Buat record baru di tabel VALIDATION
        Validation::create([
            'att_id' => $request->att_id,
            'admin_id' => $adminEmpId,
            'status_validasi' => $request->status_validasi,
            'catatan_validasi' => $request->catatan_validasi,
            'timestamp_validasi' => now()
        ]);

        return redirect()->route('admin.validasi.show')
                         ->with('success', 'Validasi absensi berhasil disimpan.');
    }

    public function submitValidasiIzin(Request $request)
    {
        $request->validate([
            'leave_id' => 'required|exists:leaves,leave_id',
            'status' => 'required|in:disetujui,ditolak',
            'catatan_admin' => 'nullable|string|max:500',
        ]);

        $leave = Leave::findOrFail($request->leave_id);

        $leave->status = $request->status;
        $leave->catatan_admin = $request->catatan_admin;
        $leave->save();

        $pesan = $request->status == 'disetujui' ? 'Izin berhasil disetujui.' : 'Izin telah ditolak.';

        return redirect()->route('admin.validasi.show')
                         ->with('success', $pesan);
    }
}
