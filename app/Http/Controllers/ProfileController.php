<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Employee;

class ProfileController extends Controller
{
    public function index()
    {
        // Ambil user yang sedang login beserta data karyawannya
        $user = Auth::user();
        $employee = $user->employee;

        // Tentukan view berdasarkan role
        if ($user->role === 'Admin') {
            return view('admin.profil', compact('user', 'employee'));
        } else {
            return view('karyawan.profil', compact('user', 'employee'));
        }
    }

    public function update(Request $request)
    {
        Log::info('=== PROFILE UPDATE START ===');
        Log::info('Request Method: ' . $request->method());
        Log::info('Request Data:', $request->all());

        $user = Auth::user();
        
        Log::info('User Info:', [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'role' => $user->role
        ]);

        if (!$user->employee) {
            Log::error('Employee relation not found for user: ' . $user->user_id);
            if ($user->role === 'Admin') {
                return redirect()->route('admin.profil')->with('error', 'Data karyawan tidak ditemukan.');
            } else {
                return redirect()->route('karyawan.profil')->with('error', 'Data karyawan tidak ditemukan.');
            }
        }

        $employee = $user->employee;

        Log::info('Employee Info:', [
            'emp_id' => $employee->emp_id,
            'nama' => $employee->nama,
            'nip' => $employee->nip
        ]);

        // Validasi untuk admin (lebih lengkap)
        if ($user->role === 'Admin') {
            Log::info('Validating Admin data...');
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'nip' => 'nullable|string|max:50',
                'username' => 'required|string|max:100|unique:USER,username,' . $user->user_id . ',user_id',
                'email' => 'required|email|max:100|unique:USER,email,' . $user->user_id . ',user_id',
                'posisi' => 'nullable|string|max:100',
                'departemen' => 'nullable|string|max:100',
                'alamat' => 'nullable|string|max:500',
                'no_telepon' => 'nullable|string|max:20',
                'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'hapus_foto' => 'sometimes|in:0,1' // ✅ PERBAIKAN: Validasi field hapus_foto
            ]);
            Log::info('Admin validation passed', $validated);
        } else {
            Log::info('Validating Karyawan data...');
            $validated = $request->validate([
                'alamat' => 'nullable|string|max:500',
                'no_telepon' => 'nullable|string|max:20',
                'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'hapus_foto' => 'sometimes|in:0,1' // ✅ PERBAIKAN: Validasi field hapus_foto
            ]);
            Log::info('Karyawan validation passed', $validated);
        }

        DB::beginTransaction();
        
        try {
            Log::info('Starting database transaction...');

            if ($user->role === 'Admin') {
                Log::info('Processing Admin update...');
                
                // Update User data
                $userUpdateResult = DB::table('USER')
                    ->where('user_id', $user->user_id)
                    ->update([
                        'username' => $request->username,
                        'email' => $request->email,
                    ]);

                Log::info('User update result:', ['affected_rows' => $userUpdateResult]);

                // Update Employee data
                $employeeData = [
                    'nama' => $request->nama,
                    'nip' => $request->nip,
                    'posisi' => $request->posisi,
                    'departemen' => $request->departemen,
                    'alamat' => $request->alamat,
                    'no_telepon' => $request->no_telepon,
                ];

                // ✅ PERBAIKAN: Handle Hapus Foto
                if ($request->has('hapus_foto') && $request->hapus_foto == '1') {
                    Log::info('Processing photo deletion...');
                    
                    if ($employee->foto_profil) {
                        $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                        Log::info('Deleting old photo:', ['path' => $oldPath]);
                        Storage::disk('public')->delete($oldPath);
                    }
                    
                    $employeeData['foto_profil'] = null;
                    Log::info('Photo marked for deletion');
                }

                // Handle Upload Foto Baru
                if ($request->hasFile('foto_profil')) {
                    Log::info('Processing photo upload...');
                    
                    // Hapus foto lama jika ada
                    if ($employee->foto_profil) {
                        $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                        Log::info('Deleting old photo:', ['path' => $oldPath]);
                        Storage::disk('public')->delete($oldPath);
                    }

                    $file = $request->file('foto_profil');
                    $fileName = $employee->emp_id . '-profil-' . now()->format('YmdHis') . '.' . $file->extension();
                    Log::info('New photo filename:', ['filename' => $fileName]);
                    
                    $path = $file->storeAs('foto_profil', $fileName, 'public');
                    $employeeData['foto_profil'] = Storage::url($path);
                    
                    Log::info('Photo stored successfully:', ['path' => $path, 'url' => $employeeData['foto_profil']]);
                }

                Log::info('Final employee data to update:', $employeeData);

                // Update Employee menggunakan Query Builder
                $employeeUpdateResult = DB::table('EMPLOYEE')
                    ->where('emp_id', $employee->emp_id)
                    ->update($employeeData);

                Log::info('Employee update result:', ['affected_rows' => $employeeUpdateResult]);

            } else {
                Log::info('Processing Karyawan update...');
                
                // Untuk karyawan, update field yang diizinkan
                $employee->alamat = $request->alamat;
                $employee->no_telepon = $request->no_telepon;

                // ✅ PERBAIKAN: Handle Hapus Foto untuk karyawan
                if ($request->has('hapus_foto') && $request->hapus_foto == '1') {
                    Log::info('Processing karyawan photo deletion...');
                    
                    if ($employee->foto_profil) {
                        $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                        Storage::disk('public')->delete($oldPath);
                    }
                    
                    $employee->foto_profil = null;
                    Log::info('Karyawan photo marked for deletion');
                }

                // Handle Upload Foto untuk karyawan
                if ($request->hasFile('foto_profil')) {
                    Log::info('Processing karyawan photo upload...');
                    
                    if ($employee->foto_profil) {
                        $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                        Storage::disk('public')->delete($oldPath);
                    }

                    $file = $request->file('foto_profil');
                    $fileName = $employee->emp_id . '-profil-' . now()->format('YmdHis') . '.' . $file->extension();
                    $path = $file->storeAs('foto_profil', $fileName, 'public');
                    $employee->foto_profil = Storage::url($path);
                    
                    Log::info('Karyawan photo stored:', ['path' => $path]);
                }

                // Untuk karyawan, gunakan save() karena field yang diupdate sudah di fillable
                $employeeSaved = $employee->save();
                Log::info('Karyawan save result:', ['saved' => $employeeSaved]);
            }
            
            DB::commit();
            Log::info('Database transaction committed successfully');

            $successMessage = 'Profil berhasil diperbarui.';
            
            // ✅ PERBAIKAN: Tambahkan pesan khusus jika foto dihapus
            if ($request->has('hapus_foto') && $request->hapus_foto == '1') {
                $successMessage .= ' Foto profil telah dihapus.';
            }
            
            Log::info('Update successful: ' . $successMessage);

            // Redirect ke route yang benar berdasarkan role
            if ($user->role === 'Admin') {
                return redirect()->route('admin.profil')->with('success', $successMessage);
            } else {
                return redirect()->route('karyawan.profil')->with('success', $successMessage);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            $errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
            Log::error('Profile update failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Redirect ke route yang benar berdasarkan role
            if ($user->role === 'Admin') {
                return redirect()->route('admin.profil')->with('error', $errorMessage);
            } else {
                return redirect()->route('karyawan.profil')->with('error', $errorMessage);
            }
        } finally {
            Log::info('=== PROFILE UPDATE END ===');
        }
    }

    /**
     * Hapus foto profil admin (method lama - bisa dihapus jika tidak digunakan)
     */
    public function deleteFotoAdmin()
    {
        // Method ini bisa dihapus karena sekarang hapus foto dilakukan melalui form update
        return redirect()->route('admin.profil')->with('error', 'Method tidak digunakan. Gunakan tombol "Simpan Perubahan" setelah memilih hapus foto.');
    }

    /**
     * Hapus foto profil karyawan (method lama - bisa dihapus jika tidak digunakan)
     */
    public function deleteFotoKaryawan()
    {
        // Method ini bisa dihapus karena sekarang hapus foto dilakukan melalui form update
        return redirect()->route('karyawan.profil')->with('error', 'Method tidak digunakan. Gunakan tombol "Simpan Perubahan" setelah memilih hapus foto.');
    }
}