<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Models\Validation;
use App\Models\WorkArea;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    private const WORK_AREA_ID = 1;
    private const ALLOWED_IMAGE_TYPES = 'jpeg,png,jpg';
    private const MAX_IMAGE_SIZE = 2048;
    private const ATTENDANCE_TYPES = ['masuk', 'pulang'];
    private const LEAVE_TYPES = ['sakit', 'izin', 'cuti'];

    // Regex untuk Teks Umum/Alamat/Deskripsi: Huruf, Angka, Spasi, ., , -, /
    private const GENERAL_TEXT_REGEX = '/^[a-zA-Z0-9\s\.\,\-\/]+$/';
    
    // Regex untuk NAMA: Huruf dan Spasi SAJA (Strict)
    private const NAMA_TEXT_REGEX = '/^[a-zA-Z\s]+$/'; 
    
    // Regex untuk Username/Email (Hanya Admin Management): Huruf, Angka, ., -, _, @
    private const USER_EMAIL_REGEX = '/^[a-zA-Z0-9\.\-\_@]+$/';
    // Regex untuk Telepon (Hanya Angka)
    private const PHONE_REGEX = '/^[0-9]+$/';

    // =================================================================
    // DASHBOARD
    // =================================================================

    public function dashboard(): \Illuminate\View\View
    {
        $today = Carbon::today();

        $totalEmployees = Employee::count();

        $presentCount = Attendance::whereDate('waktu_unggah', $today)
            ->where('type', 'masuk')
            ->distinct('emp_id')
            ->count('emp_id');

        $izinCount = $this->getApprovedLeaveCount($today, 'izin');
        $sakitCount = $this->getApprovedLeaveCount($today, 'sakit');

        $recentActivities = $this->getRecentActivities();

        [$chartLabels, $chartData] = $this->getAttendanceTrend();

        return view('admin.dashboard', compact(
            'totalEmployees',
            'presentCount',
            'izinCount',
            'sakitCount',
            'recentActivities',
            'chartLabels',
            'chartData'
        ));
    }

    private function getApprovedLeaveCount(Carbon $date, string $type): int
    {
        return Leave::where('tipe_izin', $type)
            ->where('status', 'disetujui')
            ->whereDate('tanggal_mulai', '<=', $date)
            ->whereDate('tanggal_selesai', '>=', $date)
            ->count();
    }

    private function getRecentActivities(): \Illuminate\Support\Collection
    {
        $recentAttendances = Attendance::whereDoesntHave('validation')
            ->with('employee')
            ->orderBy('waktu_unggah', 'desc')
            ->take(5)
            ->get();

        $recentLeaves = Leave::with('employee')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return $recentAttendances
            ->concat($recentLeaves)
            ->sortByDesc(fn($item) => $item->waktu_unggah ?? $item->created_at)
            ->take(6);
    }

    private function getAttendanceTrend(): array
    {
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d M');

            $data[] = Attendance::whereDate('waktu_unggah', $date)
                ->where('type', 'masuk')
                ->distinct('emp_id')
                ->count('emp_id');
        }

        return [$labels, $data];
    }

    // =================================================================
    // ATTENDANCE MANAGEMENT (MANAJEMEN ABSENSI)
    // =================================================================

    public function showManajemenAbsensi(): \Illuminate\View\View
    {
        $attendances = Attendance::with(['employee', 'validation'])
            ->orderBy('waktu_unggah', 'desc')
            ->get();

        $currentEmpId = $this->getCurrentEmployeeId();
        $employees = Employee::where('emp_id', '!=', $currentEmpId)
            ->orderBy('nama')
            ->get();

        return view('admin.manajemen_absensi', compact('attendances', 'employees'));
    }

    public function storeAbsensi(Request $request): \Illuminate\Http\RedirectResponse
    {
        // Pesan error khusus untuk regex
        $messages = [
            'catatan_admin.regex' => 'Catatan Admin tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
        ];

        // 1. Validasi Input
        $validated = $request->validate([
            'emp_id' => 'required|exists:employee,emp_id',
            'waktu_unggah' => 'required|date',
            'type' => 'required|in:' . implode(',', self::ATTENDANCE_TYPES),
            'foto' => 'nullable|image|mimes:' . self::ALLOWED_IMAGE_TYPES . '|max:' . self::MAX_IMAGE_SIZE,
            'status_validasi' => 'required|in:Valid,Invalid,Pending', // Ambil input dari form
            'catatan_admin' => ['nullable', 'string', 'max:255', 'regex:' . self::GENERAL_TEXT_REGEX],
        ], $messages);

        if ($this->isSelfAttendance($validated['emp_id'])) {
            return redirect()->back()
                ->with('error', 'AKSES DITOLAK: Anda tidak boleh menginput absensi manual untuk diri sendiri.');
        }

        return $this->handleAttendanceStorage($validated, $request);
    }

    public function updateAbsensi(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $attendance = Attendance::findOrFail($id);

        if ($this->isSelfAttendance($attendance->emp_id)) {
            return redirect()->back()
                ->with('error', 'AKSES DITOLAK: Anda tidak dapat mengedit data absensi milik sendiri.');
        }

        // Pesan error khusus untuk regex
        $messages = [
            'catatan_admin.regex' => 'Catatan Admin tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
        ];

        $validated = $request->validate([
            'waktu_unggah' => 'required|date',
            'type' => 'required|in:' . implode(',', self::ATTENDANCE_TYPES),
            'foto' => 'nullable|image|max:' . self::MAX_IMAGE_SIZE,
            'status_validasi' => 'required|in:Valid,Invalid,Pending', // Ambil input dari form
            'catatan_admin' => ['nullable', 'string', 'max:255', 'regex:' . self::GENERAL_TEXT_REGEX],
        ], $messages);

        return $this->handleAttendanceUpdate($attendance, $validated, $request);
    }

    public function destroyAbsensi(int $id): \Illuminate\Http\RedirectResponse
    {
        $attendance = Attendance::findOrFail($id);

        DB::transaction(function () use ($attendance) {
            $attendance->validation?->delete();
            $attendance->delete();
        });

        return redirect()->back()->with('success', 'Data absensi berhasil dihapus.');
    }

    private function handleAttendanceStorage(array $validated, Request $request): \Illuminate\Http\RedirectResponse
    {
        DB::transaction(function () use ($validated, $request) {
            $fotoPath = $this->handlePhotoUpload($request, 'absensi', 'images/placeholder-absensi.jpg');

            $attendance = Attendance::create([
                'emp_id' => $validated['emp_id'],
                'waktu_unggah' => Carbon::parse($validated['waktu_unggah']),
                'type' => $validated['type'],
                'latitude' => 0, // Default manual
                'longitude' => 0, // Default manual
                'nama_file_foto' => $fotoPath,
            ]);

            // GUNAKAN STATUS DARI INPUT FORM ($validated['status_validasi'])
            Validation::updateOrCreate(
                ['att_id' => $attendance->att_id],
                [
                    'admin_id' => $this->getCurrentEmployeeId(),
                    'status_validasi_otomatis' => 'Need Review', // Otomatis tetap pending karena manual
                    'status_validasi_final' => $validated['status_validasi'], 
                    'catatan_admin' => $validated['catatan_admin'] ?? 'Input Manual Admin',
                    'timestamp_validasi' => now(),
                ]
            );
        });

        return redirect()->back()->with('success', 'Data absensi berhasil ditambahkan.');
    }

    private function handleAttendanceUpdate(Attendance $attendance, array $validated, Request $request): \Illuminate\Http\RedirectResponse
    {
        DB::transaction(function () use ($attendance, $validated, $request) {
            $attendance->waktu_unggah = Carbon::parse($validated['waktu_unggah']);
            $attendance->type = $validated['type'];

            if ($request->hasFile('foto')) {
                // Hapus foto lama jika ada dan bukan placeholder (opsional)
                $attendance->nama_file_foto = $this->handlePhotoUpload($request, 'absensi');
            }

            $attendance->save();

            // GUNAKAN STATUS DARI INPUT FORM ($validated['status_validasi'])
            Validation::updateOrCreate(
                ['att_id' => $attendance->att_id],
                [
                    'admin_id' => $this->getCurrentEmployeeId(),
                    'status_validasi_final' => $validated['status_validasi'], 
                    'catatan_admin' => $validated['catatan_admin'] ?? 'Update Manual Admin',
                    'timestamp_validasi' => now(),
                ]
            );
        });

        return redirect()->back()->with('success', 'Data absensi berhasil diperbarui.');
    }

    // =================================================================
    // LEAVE MANAGEMENT (MANAJEMEN IZIN)
    // =================================================================

    public function showManajemenIzin(): \Illuminate\View\View
    {
        $leaves = Leave::with('employee')->orderBy('created_at', 'desc')->get();

        $currentEmpId = $this->getCurrentEmployeeId();
        $employees = Employee::where('emp_id', '!=', $currentEmpId)
            ->orderBy('nama')
            ->get();

        return view('admin.manajemen_izin', compact('leaves', 'employees'));
    }

    public function storeIzin(Request $request): \Illuminate\Http\RedirectResponse
    {
        // Pesan error khusus untuk regex
        $messages = [
            'deskripsi.regex' => 'Deskripsi Izin tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
            'catatan_admin.regex' => 'Catatan Admin tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
        ];

        $validated = $request->validate([
            'emp_id' => 'required|exists:employee,emp_id',
            'tipe_izin' => 'required|in:' . implode(',', self::LEAVE_TYPES),
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'deskripsi' => ['required', 'string', 'max:500', 'regex:' . self::GENERAL_TEXT_REGEX],
            'status' => 'required|in:disetujui,pending,ditolak', // Tambahkan validasi status
            'file_bukti' => 'nullable|file|mimes:jpg,jpeg,png|max:' . self::MAX_IMAGE_SIZE,
            'catatan_admin' => ['nullable', 'string', 'max:255', 'regex:' . self::GENERAL_TEXT_REGEX],
        ], $messages);

        if ($this->isSelfAttendance($validated['emp_id'])) {
            return redirect()->back()
                ->with('error', 'AKSES DITOLAK: Gunakan menu "Pengajuan Izin" di sidebar Portal Absensi.');
        }

        return $this->handleLeaveStorage($validated, $request);
    }

    public function updateIzin(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $leave = Leave::findOrFail($id);

        if ($this->isSelfAttendance($leave->emp_id)) {
            return redirect()->back()
                ->with('error', 'ETIKA: Anda tidak dapat memvalidasi pengajuan izin milik sendiri.');
        }

        // Pesan error khusus untuk regex
        $messages = [
            'deskripsi.regex' => 'Deskripsi Izin tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
            'catatan_admin.regex' => 'Catatan Admin tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
        ];

        // Validasi semua field yang bisa diedit di modal
        $validated = $request->validate([
            'tipe_izin' => 'required|in:' . implode(',', self::LEAVE_TYPES),
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'deskripsi' => ['required', 'string', 'max:500', 'regex:' . self::GENERAL_TEXT_REGEX],
            'status' => 'required|in:pending,disetujui,ditolak',
            'catatan_admin' => ['nullable', 'string', 'max:255', 'regex:' . self::GENERAL_TEXT_REGEX],
            'file_bukti' => 'nullable|file|mimes:jpg,jpeg,png|max:' . self::MAX_IMAGE_SIZE,
        ], $messages);

        DB::transaction(function () use ($leave, $validated, $request) {
            // Cek jika ada file baru diupload
            if ($request->hasFile('file_bukti')) {
                // Hapus file lama jika ada (Opsional, gunakan Storage::delete($leave->file_bukti))
                $validated['file_bukti'] = $this->handleFileUpload($request, 'file_bukti', 'bukti_izin');
            }

            // Update Semua Data
            $leave->update([
                'tipe_izin' => $validated['tipe_izin'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'deskripsi' => $validated['deskripsi'],
                'status' => $validated['status'],
                'catatan_admin' => $validated['catatan_admin'] ?? null,
                // Hanya update file_bukti jika ada file baru
                'file_bukti' => $validated['file_bukti'] ?? $leave->file_bukti
            ]);
        });

        return redirect()->back()->with('success', 'Data izin berhasil diperbarui sepenuhnya.');
    }

    public function destroyIzin(int $id): \Illuminate\Http\RedirectResponse
    {
        $leave = Leave::findOrFail($id);
        $leave->delete();

        return redirect()->back()->with('success', 'Data izin berhasil dihapus.');
    }

    private function handleLeaveStorage(array $validated, Request $request): \Illuminate\Http\RedirectResponse
    {
        DB::transaction(function () use ($validated, $request) {
            $filePath = $this->handleFileUpload($request, 'file_bukti', 'bukti_izin');

            Leave::create([
                'emp_id' => $validated['emp_id'],
                'tipe_izin' => $validated['tipe_izin'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'deskripsi' => $validated['deskripsi'],
                'file_bukti' => $filePath,
                'status' => $validated['status'], 
                'catatan_admin' => $validated['catatan_admin'] ?? 'Diinput manual oleh Admin.',
            ]);
        });

        return redirect()->back()->with('success', 'Data izin berhasil ditambahkan.');
    }

    // =================================================================
    // EMPLOYEE MANAGEMENT (MANAJEMEN KARYAWAN)
    // =================================================================

    public function showManajemenKaryawan(): \Illuminate\View\View
    {
        $employees = Employee::with('user')->orderBy('nama')->get();

        return view('admin.manajemen_karyawan', ['employee' => $employees]);
    }

    public function storeKaryawan(Request $request): \Illuminate\Http\RedirectResponse
    {
        $messages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'unique' => ':attribute sudah digunakan.',
            'email' => 'Format email tidak valid.',
            'confirmed' => 'Konfirmasi password tidak cocok.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'image' => ':attribute harus berupa gambar.',
            // Pesan error khusus regex (DIPERBARUI)
            'nama.regex' => 'Nama hanya boleh berisi huruf dan spasi. Angka, simbol, titik, atau koma tidak diperbolehkan.',
            'nip.regex' => 'NIP hanya boleh berisi angka (0-9).',
            'departemen.regex' => 'Departemen tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
            'posisi.regex' => 'Posisi tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
            'username.regex' => 'Username/Email hanya boleh mengandung huruf, angka, titik (.), strip (-), underscore (_), dan tanda @.',
            'no_telepon.regex' => 'Nomor telepon hanya boleh mengandung angka.',
            'alamat.regex' => 'Alamat hanya boleh berisi huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/).',
        ];

        // Validasi (DIPERKETAT)
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'regex:' . self::NAMA_TEXT_REGEX], // Strict Nama
            'nip' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/', 'unique:employee'], // Strict NIP (Angka)
            'departemen' => ['nullable', 'string', 'max:100', 'regex:' . self::GENERAL_TEXT_REGEX],
            'posisi' => ['nullable', 'string', 'max:100', 'regex:' . self::GENERAL_TEXT_REGEX],
            'username' => ['required', 'string', 'max:100', 'unique:user', 'regex:' . self::USER_EMAIL_REGEX],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:Karyawan,Admin,Manajemen'],
            'no_telepon' => ['nullable', 'string', 'max:20', 'regex:' . self::PHONE_REGEX],

            'alamat' => ['nullable', 'string', 'max:500', 'regex:' . self::GENERAL_TEXT_REGEX],

            'foto_profil' => ['nullable', 'image', 'mimes:' . self::ALLOWED_IMAGE_TYPES, 'max:' . self::MAX_IMAGE_SIZE],
        ], $messages);

        return $this->handleEmployeeStorage($validated, $request);
    }

    public function updateKaryawan(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $user = User::findOrFail($id);
        $employee = $user->employee;

        $messages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'unique' => ':attribute sudah digunakan.',
            'email' => 'Format email tidak valid.',
            'confirmed' => 'Konfirmasi password tidak cocok.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'image' => ':attribute harus berupa gambar.',
            // Pesan error khusus regex (DIPERBARUI)
            'nama.regex' => 'Nama hanya boleh berisi huruf dan spasi. Angka, simbol, titik, atau koma tidak diperbolehkan.',
            'nip.regex' => 'NIP hanya boleh berisi angka (0-9).',
            'departemen.regex' => 'Departemen tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
            'posisi.regex' => 'Posisi tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
            'username.regex' => 'Username/Email hanya boleh mengandung huruf, angka, titik (.), strip (-), underscore (_), dan tanda @.',
            'no_telepon.regex' => 'Nomor telepon hanya boleh mengandung angka.',
            'alamat.regex' => 'Alamat hanya boleh berisi huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/).',
        ];

        // Validasi (DIPERKETAT)
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'regex:' . self::NAMA_TEXT_REGEX], // Strict Nama
            'nip' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/', 'unique:employee,nip,' . $employee->emp_id . ',emp_id'], // Strict NIP (Angka)
            'departemen' => ['nullable', 'string', 'max:100', 'regex:' . self::GENERAL_TEXT_REGEX],
            'posisi' => ['nullable', 'string', 'max:100', 'regex:' . self::GENERAL_TEXT_REGEX],
            'username' => ['required', 'string', 'max:100', 'unique:user,username,' . $user->user_id . ',user_id', 'regex:' . self::USER_EMAIL_REGEX],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:Karyawan,Admin,Manajemen'],
            'no_telepon' => ['nullable', 'string', 'max:20', 'regex:' . self::PHONE_REGEX],

            'alamat' => ['nullable', 'string', 'max:500', 'regex:' . self::GENERAL_TEXT_REGEX],

            'foto_profil' => ['nullable', 'image', 'mimes:' . self::ALLOWED_IMAGE_TYPES, 'max:' . self::MAX_IMAGE_SIZE],
        ], $messages);

        return $this->handleEmployeeUpdate($user, $employee, $validated, $request);
    }

    public function destroyKaryawan(int $id): \Illuminate\Http\RedirectResponse
    {
        if ($id === Auth::user()->user_id) {
            return redirect()->back()->with('error', 'Tindakan Ditolak: Anda tidak dapat menghapus akun sendiri.');
        }

        $user = User::findOrFail($id);

        DB::transaction(function () use ($user) {
            $user->employee?->delete();
            $user->delete();
        });

        return redirect()->back()->with('success', 'Pengguna berhasil dihapus.');
    }

    private function handleEmployeeStorage(array $validated, Request $request): \Illuminate\Http\RedirectResponse
    {
        DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['username'] . '@klinik.com',
                'password_hash' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            $photoPath = $this->handlePhotoUpload($request, 'photos');

            Employee::create([
                'user_id' => $user->user_id,
                'nama' => $validated['nama'],
                'nip' => $validated['nip'],
                'departemen' => $validated['departemen'],
                'posisi' => $validated['posisi'],
                'no_telepon' => $validated['no_telepon'],
                'alamat' => $validated['alamat'],
                'foto_profil' => $photoPath,
                'status_aktif' => true,
            ]);
        });

        return redirect()->back()->with('success', 'User baru berhasil ditambahkan.');
    }

    private function handleEmployeeUpdate(User $user, Employee $employee, array $validated, Request $request): \Illuminate\Http\RedirectResponse
    {
        DB::transaction(function () use ($user, $employee, $validated, $request) {
            $user->update([
                'username' => $validated['username'],
                'role' => $validated['role'],
                'password_hash' => $validated['password'] ? Hash::make($validated['password']) : $user->password_hash,
            ]);

            if ($request->hasFile('foto_profil')) {
                $employee->foto_profil = $this->handlePhotoUpload($request, 'photos');
            }

            $employee->update([
                'nama' => $validated['nama'],
                'nip' => $validated['nip'],
                'departemen' => $validated['departemen'],
                'posisi' => $validated['posisi'],
                'no_telepon' => $validated['no_telepon'],
                'alamat' => $validated['alamat'],
            ]);
        });

        return redirect()->back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    // =================================================================
    // REPORT & GEOFENCING
    // =================================================================

    public function exportLaporan(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $workConfig = $this->getWorkAreaConfig();
        $employees = Employee::orderBy('nama')->get();

        $filename = "Laporan-Absensi_{$startDate->format('Ymd')}-{$endDate->format('Ymd')}.csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        return response()->stream(
            fn() => $this->generateReport($employees, $startDate, $endDate, $workConfig),
            200,
            $headers
        );
    }

    private function generateReport($employees, Carbon $startDate, Carbon $endDate, array $workConfig): void
    {
        $file = fopen('php://output', 'w');

        fputcsv($file, ['NIP', 'Nama', 'Hadir', 'Terlambat', 'Izin/Sakit', '% Kehadiran']);

        foreach ($employees as $employee) {
            $attendances = Attendance::where('emp_id', $employee->emp_id)
                ->whereBetween('waktu_unggah', [$startDate, $endDate])
                ->where('type', 'masuk')
                ->get();

            $totalPresent = $attendances->count();
            $totalLate = $attendances->filter(
                fn($att) => $att->waktu_unggah->format('H:i:s') > $workConfig['jam_masuk']
            )->count();

            $totalLeave = Leave::where('emp_id', $employee->emp_id)
                ->where('status', 'disetujui')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
                        ->orWhereBetween('tanggal_selesai', [$startDate, $endDate]);
                })
                ->count();

            $totalWorkDays = $this->countWorkingDaysInRange($startDate, $endDate, $workConfig['hari_kerja']);
            $attendanceRate = $totalWorkDays > 0 ? ($totalPresent / $totalWorkDays) * 100 : 0;

            fputcsv($file, [
                $this->sanitizeCsvValue($employee->nip),
                $this->sanitizeCsvValue($employee->nama),
                $totalPresent,
                $totalLate,
                $totalLeave,
                number_format(min($attendanceRate, 100), 1)
            ]);
        }

        fclose($file);
    }

    private function getWorkAreaConfig(): array
    {
        $workArea = WorkArea::find(self::WORK_AREA_ID);

        return [
            'jam_masuk' => $workArea?->jam_kerja['masuk'] ?? '08:00:00',
            'hari_kerja' => $workArea?->jam_kerja['hari_kerja'] ?? [1, 2, 3, 4, 5],
        ];
    }

    private function countWorkingDaysInRange(Carbon $startDate, Carbon $endDate, array $validDays): int
    {
        $count = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if (in_array($current->dayOfWeek, $validDays)) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    private function sanitizeCsvValue($value): string
    {
        $stringValue = (string) $value;

        return preg_match('/^[\=\+\-\@]/', $stringValue)
            ? "'" . $stringValue
            : $stringValue;
    }

    public function showGeofencing(): \Illuminate\View\View
    {
        $location = WorkArea::select(
            'area_id', 'nama_area', 'radius_geofence', 'jam_kerja',
            // BENAR: ST_Y mengambil Latitude (Y), ST_X mengambil Longitude (X)
            DB::raw('ST_Y(koordinat_pusat) as latitude'),
            DB::raw('ST_X(koordinat_pusat) as longitude')
        )->where('area_id', self::WORK_AREA_ID)->first();

                // Samakan nama variabel yang dikirim ke view menjadi 'lokasi'
        return view('admin.geofencing', ['lokasi' => $location]);
            }

    public function saveGeofencing(Request $request): \Illuminate\Http\RedirectResponse
    {
        // Pesan error khusus untuk regex
        $messages = [
            'nama_area.regex' => 'Nama Area tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
        ];

        $validated = $request->validate([
            'nama_area' => ['required', 'string', 'max:100', 'regex:' . self::GENERAL_TEXT_REGEX],
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric|min:50',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'hari_kerja' => 'array'
        ], $messages);

        $workArea = WorkArea::firstOrNew(['area_id' => self::WORK_AREA_ID]);
        $workArea->fill([
            'nama_area' => $validated['nama_area'],
            'radius_geofence' => $validated['radius'],
           'koordinat_pusat' => DB::raw("POINT({$validated['longitude']}, {$validated['latitude']})"),
            'jam_kerja' => [
                'masuk' => $validated['jam_masuk'],
                'pulang' => $validated['jam_pulang'],
                'hari_kerja' => array_map('intval', $validated['hari_kerja'] ?? [])
            ]
        ]);
        $workArea->save();

        return redirect()->route('admin.geofencing.show')
            ->with('success', 'Lokasi & Jam Kerja berhasil diperbarui!');
    }

    // =================================================================
    // SELF ATTENDANCE (ADMIN SELF-SERVICE)
    // =================================================================
    
    public function showProfil(): \Illuminate\View\View
    {
        $user = Auth::user();
        // Asumsi admin juga punya entri di tabel employee untuk data profil
        $employee = $user->employee; 
        
        return view('admin.profil', compact('user', 'employee'));
    }

    public function updateProfil(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
             return redirect()->back()->with('error', 'Data karyawan Admin tidak ditemukan. Hubungi IT.');
        }

        $messages = [
            'nama.regex' => 'Nama tidak boleh mengandung simbol spesial atau angka. Hanya huruf, spasi, titik (.), koma (,), dan strip (-) yang diizinkan.',
            'nip.regex' => 'NIP hanya boleh mengandung huruf dan angka.',
            'alamat.regex' => 'Alamat tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
            'username.regex' => 'Username hanya boleh mengandung huruf, angka, titik (.), strip (-), underscore (_), dan tanda @.',
            'posisi.regex' => 'Jabatan/Posisi tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
            'departemen.regex' => 'Departemen tidak boleh mengandung simbol spesial. Hanya huruf, angka, spasi, titik (.), koma (,), strip (-), dan garis miring (/) yang diizinkan.',
            'no_telepon.regex' => 'Nomor telepon hanya boleh mengandung angka.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
        ];

        // Validasi diperketat untuk input dari form profil admin
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'regex:' . self::NAMA_TEXT_REGEX],
            'nip' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9]+$/', 'unique:employee,nip,' . $employee->emp_id . ',emp_id'], 
            'username' => ['required', 'string', 'max:100', 'unique:user,username,' . $user->user_id . ',user_id', 'regex:' . self::USER_EMAIL_REGEX],
            'email' => ['required', 'email', 'max:255', 'unique:user,email,' . $user->user_id . ',user_id'], 
            'posisi' => ['nullable', 'string', 'max:100', 'regex:' . self::GENERAL_TEXT_REGEX],
            'departemen' => ['nullable', 'string', 'max:100', 'regex:' . self::GENERAL_TEXT_REGEX],
            'no_telepon' => ['nullable', 'string', 'max:20', 'regex:' . self::PHONE_REGEX],
            'alamat' => ['nullable', 'string', 'max:500', 'regex:' . self::GENERAL_TEXT_REGEX],
            'foto_profil' => ['nullable', 'image', 'mimes:' . self::ALLOWED_IMAGE_TYPES, 'max:' . self::MAX_IMAGE_SIZE],
            'hapus_foto' => ['in:0,1'],
        ], $messages);

        DB::transaction(function () use ($user, $employee, $validated, $request) {
            $user->update([
                'username' => $validated['username'],
                'email' => $validated['email'],
                // Password tidak diupdate di form ini
            ]);

            $fotoPath = $employee->foto_profil;
            if (isset($validated['hapus_foto']) && $validated['hapus_foto'] == 1) {
                // Hapus foto lama jika diminta
                if ($fotoPath) {
                    Storage::delete(str_replace('/storage/', 'public/', $fotoPath));
                }
                $fotoPath = null;
            } elseif ($request->hasFile('foto_profil')) {
                // Upload foto baru jika ada
                $fotoPath = $this->handlePhotoUpload($request, 'photos');
            }

            $employee->update([
                'nama' => $validated['nama'],
                'nip' => $validated['nip'] ?? $employee->nip,
                'departemen' => $validated['departemen'] ?? $employee->departemen,
                'posisi' => $validated['posisi'] ?? $employee->posisi,
                'no_telepon' => $validated['no_telepon'],
                'alamat' => $validated['alamat'],
                'foto_profil' => $fotoPath,
            ]);
        });
        return redirect()->route('admin.profil');
    }
    
    public function showUnggah(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if (!$user->employee) {
            return back()->with('error', 'Akun Admin ini belum terhubung ke data Karyawan.');
        }

        $today = Carbon::today();
        $absensiMasuk = Attendance::where('emp_id', $user->employee->emp_id)->whereDate('waktu_unggah', $today)->where('type', 'masuk')->first();
        $absensiPulang = Attendance::where('emp_id', $user->employee->emp_id)->whereDate('waktu_unggah', $today)->where('type', 'pulang')->first();

        $workArea = WorkArea::select(
            'radius_geofence',
            DB::raw('ST_X(koordinat_pusat) as latitude'),
            DB::raw('ST_Y(koordinat_pusat) as longitude')
        )->first();

        return view('admin.absensi.unggah', compact('absensiMasuk', 'absensiPulang', 'workArea'));
    }

    public function storeFoto(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'foto_absensi' => 'required|image|mimes:' . self::ALLOWED_IMAGE_TYPES . '|max:10240',
            'type' => 'required|in:' . implode(',', self::ATTENDANCE_TYPES),
            'browser_lat' => 'required|numeric',
            'browser_lng' => 'required|numeric',
        ]);

        $employeeId = Auth::user()->employee->emp_id;
        $file = $request->file('foto_absensi');

        if (!$this->validateAttendancePhoto($file, $employeeId, $validated['browser_lat'], $validated['browser_lng'])) {
            return redirect()->back()->with('error', session('validation_error'));
        }

        $this->storeAttendancePhoto($file, $employeeId, $validated);

        return redirect()->route('admin.absensi.riwayat')
            ->with('success', 'Absensi berhasil dicatat!')
            ->withCookie(cookie('device_owner_id', $employeeId, 2628000));
    }

    private function validateAttendancePhoto($file, int $employeeId, float $browserLat, float $browserLng): bool
    {
        if (!$this->detectFace($file->getRealPath())) {
            session(['validation_error' => 'VALIDASI WAJAH GAGAL: Sistem AI tidak menemukan wajah.']);
            return false;
        }

        if (!$this->validateDeviceLock($employeeId)) {
            session(['validation_error' => 'KEAMANAN: Perangkat ini terdaftar atas nama karyawan lain.']);
            return false;
        }

        if (!$this->validatePhotoUniqueness($file)) {
            session(['validation_error' => 'Foto ini sudah pernah digunakan sebelumnya.']);
            return false;
        }

        if (!$this->validateGeofence($browserLat, $browserLng)) {
            return false;
        }

        return true;
    }

    private function validateDeviceLock(int $employeeId): bool
    {
        $deviceOwner = request()->cookie('device_owner_id');

        return !$deviceOwner || $deviceOwner == $employeeId;
    }

    private function validatePhotoUniqueness($file): bool
    {
        $fileHash = md5_file($file->getRealPath());

        return !Attendance::where('file_hash', $fileHash)->exists();
    }

    private function validateGeofence(float $lat, float $lng): bool
    {
        $workArea = WorkArea::select(
            'radius_geofence',
            DB::raw('ST_X(koordinat_pusat) as latitude'),
            DB::raw('ST_Y(koordinat_pusat) as longitude')
        )->find(self::WORK_AREA_ID);

        if (!$workArea) {
            session(['validation_error' => 'Lokasi kantor belum diset.']);
            return false;
        }

        $distance = $this->haversineDistance($lat, $lng, $workArea->latitude, $workArea->longitude);

        if ($distance > $workArea->radius_geofence) {
            session(['validation_error' => "Anda berada di luar jangkauan kantor ({$distance} meter)."]);
            return false;
        }

        return true;
    }

    private function storeAttendancePhoto($file, int $employeeId, array $validated): void
    {
        $fileName = "{$employeeId}-" . now()->format('Ymd-His') . "-{$validated['type']}." . $file->extension();
        $path = $file->storeAs('public/absensi', $fileName);
        $publicPath = Storage::url($path);

        $exif = @exif_read_data($file->getRealPath());
        $exifLat = $exif['GPSLatitude'] ?? null
            ? $this->gpsDmsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N')
            : $validated['browser_lat'];
        $exifLng = $exif['GPSLongitude'] ?? null
            ? $this->gpsDmsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E')
            : $validated['browser_lng'];

        Attendance::create([
            'emp_id' => $employeeId,
            'area_id' => self::WORK_AREA_ID,
            'waktu_unggah' => now(),
            'latitude' => $exifLat,
            'longitude' => $exifLng,
            'nama_file_foto' => $publicPath,
            'timestamp_ekstraksi' => $exif['DateTimeOriginal'] ?? now(),
            'type' => $validated['type'],
            'file_hash' => md5_file($file->getRealPath())
        ]);
    }

    public function showRiwayat(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if (!$user->employee) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Data karyawan tidak ditemukan.');
        }

        $employee = $user->employee;

        $riwayatAbsensi = Attendance::with('validation')
            ->where('emp_id', $employee->emp_id)
            ->orderBy('waktu_unggah', 'desc')
            ->get();

        $leaveStats = $this->getLeaveStats($employee->emp_id);
        $riwayatIzin = Leave::where('emp_id', $employee->emp_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.absensi.riwayat', array_merge(
            compact('riwayatAbsensi', 'employee', 'riwayatIzin'),
            $leaveStats
        ));
    }

    private function getLeaveStats(int $employeeId): array
    {
        return [
            'izinCount' => Leave::where('emp_id', $employeeId)->where('tipe_izin', 'izin')->where('status', 'disetujui')->count(),
            'sakitCount' => Leave::where('emp_id', $employeeId)->where('tipe_izin', 'sakit')->where('status', 'disetujui')->count(),
            'cutiCount' => Leave::where('emp_id', $employeeId)->where('tipe_izin', 'cuti')->where('status', 'disetujui')->count(),
        ];
    }

    public function showIzin(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $riwayatIzin = Leave::where('emp_id', Auth::user()->employee->emp_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.absensi.izin', compact('riwayatIzin'));
    }

    // =================================================================
    // FILE UPLOAD HELPERS
    // =================================================================

    private function handlePhotoUpload(Request $request, string $folder, ?string $default = null): ?string
    {
        // Periksa 'foto' (untuk absensi manual) atau 'foto_profil' (untuk update profil)
        $fileInputName = $request->hasFile('foto') ? 'foto' : ($request->hasFile('foto_profil') ? 'foto_profil' : null);

        if (!$fileInputName) {
            return $default;
        }

        $file = $request->file($fileInputName);
        $path = $file->store("public/{$folder}");

        return str_replace('public/', 'storage/', $path);
    }

    private function handleFileUpload(Request $request, string $fieldName, string $folder): ?string
    {
        if (!$request->hasFile($fieldName)) {
            return null;
        }

        $file = $request->file($fieldName);
        $fileName = $request->emp_id . '-' . now()->format('YmdHis') . '-adminup.' . $file->extension();
        $path = $file->storeAs("public/{$folder}", $fileName);

        return str_replace('public/', 'storage/', $path);
    }

    // =================================================================
    // VALIDATION HELPERS
    // =================================================================

    private function getCurrentEmployeeId(): int
    {
        return Auth::user()->employee->emp_id ?? 0;
    }

    private function isSelfAttendance(int $targetEmployeeId): bool
    {
        return $targetEmployeeId === $this->getCurrentEmployeeId();
    }

    // =================================================================
    // PYTHON & GEOLOCATION HELPERS
    // =================================================================

    private function detectFace(string $imagePath): bool
    {
        try {
            $scriptPath = base_path('app/Python/detect_face.py');
            $pythonCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'python' : 'python3';

            $command = sprintf(
                '%s %s %s 2>&1',
                $pythonCmd,
                escapeshellarg($scriptPath),
                escapeshellarg($imagePath)
            );

            $output = trim(shell_exec($command));

            return $output === 'true';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function gpsDmsToDecimal(array $dmsArray, string $ref): float
    {
        $evalCoord = fn($part) => count($parts = explode('/', $part)) === 2
            ? ($parts[1] == 0 ? 0 : $parts[0] / $parts[1])
            : (float)$parts[0];

        $decimal = $evalCoord($dmsArray[0]) +
            ($evalCoord($dmsArray[1]) / 60) +
            ($evalCoord($dmsArray[2]) / 3600);

        return in_array($ref, ['S', 'W']) ? -$decimal : $decimal;
    }

    public function checkExif(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'foto_absensi' => 'required|image|mimes:' . self::ALLOWED_IMAGE_TYPES . '|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'File terlalu besar (>10MB) atau bukan gambar.'
            ], 422);
        }

        $file = $request->file('foto_absensi');
        $fileHash = md5_file($file->getRealPath());

        if (Attendance::where('file_hash', $fileHash)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Foto ini sudah pernah digunakan sebelumnya.'
            ], 400);
        }

        if (!function_exists('exif_read_data')) {
            return response()->json([
                'status' => 'success',
                'message' => 'Warning: EXIF Server non-aktif, validasi dilewati.'
            ]);
        }

        $exif = @exif_read_data($file->getRealPath());

        if (!$exif || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Lokasi (GPS) tidak ditemukan pada foto.'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Foto Valid.'
        ]);
    }
}