<?php

namespace App\Http\Controllers;

// --- SEMUA USE STATEMENT DIKUMPULKAN DI SINI ---
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Models\Validation;
use App\Models\WorkArea;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;

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
        // 1. Ambil jumlah total karyawan
        $totalKaryawan = Employee::count();

        // 2. Ambil jumlah absensi yang 'pending'
        //    (Absensi yang belum punya data di tabel VALIDATION)
        $pendingValidasi = Attendance::whereDoesntHave('validation')->count();

        // 3. Ambil jumlah absensi yang HARI INI (Berdasarkan 'waktu_unggah')
        $absensiHariIni = Attendance::whereDate('waktu_unggah', today())->count();

        // 4. Ambil 5 aktivitas absensi terbaru (Berdasarkan 'waktu_unggah')
        $aktivitasTerbaru = Attendance::with('employee') // 'employee' adalah nama relasi
                                    ->orderBy('waktu_unggah', 'desc') // Diubah ke 'waktu_unggah'
                                    ->take(5)
                                    ->get();

        // 5. Kirim semua data ke view
        return view('admin.dashboard', [
            'totalKaryawan' => $totalKaryawan,
            'pendingValidasi' => $pendingValidasi,
            'absensiHariIni' => $absensiHariIni,
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ]);
    }

    /**
     * Menampilkan halaman validasi absensi.
     */
    public function showValidasi()
    {
        // TODO: Ambil data absensi yang perlu divalidasi
        // $attendances = Attendance::whereDoesntHave('validation')->with('employee')->get();

        return view('admin.validasi');
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
        // TODO: Logika untuk memfilter data dan mengekspor ke Excel/PDF
        return redirect()->back()->with('success', 'Laporan sedang diproses.');
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

    public function showValidasiPage()
    {
        // Ambil semua data absensi (ATTENDANCE)
        // yang BELUM MEMILIKI relasi 'validation' (datanya belum ada di tabel VALIDATION)
        $pendingAttendances = Attendance::whereDoesntHave('validation')
            ->with('employee') // Ambil data karyawan terkait
            ->orderBy('waktu_unggah', 'desc') // Tampilkan yg terbaru dulu
            ->get();

        return view('admin.validasi', ['attendances' => $pendingAttendances]);
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
}
