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
        $user = Auth::user();
        $employee = $user->employee;

        $role = strtolower(trim($user->role));

        if ($role === 'admin') {
            return view('admin.profil', compact('user', 'employee'));
        } elseif ($role === 'manajemen') {
            return view('manajemen.profil', compact('user', 'employee'));
        } else {
            return view('karyawan.profil', compact('user', 'employee'));
        }
    }

    public function update(Request $request)
    {
        Log::info('=== PROFILE UPDATE START ===');
        Log::info('Request Method: ' . $request->method());
        Log::info('Request Data:', $request->all());
        Log::info('Files Data:', $request->file() ?: ['no_files' => true]);
        Log::info('hapus_foto value:', ['hapus_foto' => $request->input('hapus_foto')]);

        $user = Auth::user();
        $role = strtolower(trim($user->role));
        
        Log::info('User Info:', [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'role' => $user->role
        ]);

        if (!$user->employee) {
            Log::error('Employee relation not found for user: ' . $user->user_id);
            
            if ($role === 'admin') {
                return redirect()->route('admin.profil')->with('error', 'Data karyawan tidak ditemukan.');
            } elseif ($role === 'manajemen') {
                return redirect()->route('manajemen.profil')->with('error', 'Data karyawan tidak ditemukan.');
            } else {
                return redirect()->route('karyawan.profil')->with('error', 'Data karyawan tidak ditemukan.');
            }
        }

        $employee = $user->employee;

        Log::info('Employee Info:', [
            'emp_id' => $employee->emp_id,
            'nama' => $employee->nama,
            'nip' => $employee->nip,
            'current_foto_profil' => $employee->foto_profil
        ]);

        // Regex untuk Nama: Hanya huruf dan spasi
        $nameRegex = 'regex:/^[a-zA-Z\s]+$/';
        // Regex untuk Alamat: Angka, Huruf, Spasi, Titik, Koma, Strip, Garis Miring
        $addressRegex = 'regex:/^[a-zA-Z0-9\s.,\-\/]+$/';

        $messages = [
            'nama.regex' => 'Nama hanya boleh berisi huruf dan spasi.',
            'alamat.regex' => 'Alamat hanya boleh berisi huruf, angka, titik, koma, strip (-), dan garis miring (/).',
            'nip.regex' => 'NIP hanya boleh berisi angka (0-9).',
        ];

        if ($role === 'admin') {
            Log::info('Validating Admin data...');
            $validated = $request->validate([
                'nama' => ['required', 'string', 'max:255', $nameRegex],
                'nip' => 'nullable|string|max:50|regex:/^[0-9]+$/', // Admin bisa edit NIP, validasi angka
                'username' => 'required|string|max:100|unique:USER,username,' . $user->user_id . ',user_id',
                'email' => 'required|email|max:100|unique:USER,email,' . $user->user_id . ',user_id',
                'posisi' => 'nullable|string|max:100',
                'departemen' => 'nullable|string|max:100',
                'alamat' => ['nullable', 'string', 'max:500', $addressRegex],
                'no_telepon' => 'nullable|string|max:20',
                'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ], $messages);
            Log::info('Admin validation passed', $validated);
        } else {
            Log::info('Validating User data...');
            $validated = $request->validate([
                'nama' => ['required', 'string', 'max:255', $nameRegex], 
                'alamat' => ['nullable', 'string', 'max:500', $addressRegex],
                'no_telepon' => 'nullable|string|max:20',
                'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ], $messages);
            Log::info('User validation passed', $validated);
        }

        DB::beginTransaction();
        
        try {
            Log::info('Starting database transaction...');

            if ($role === 'admin') {
                Log::info('Processing Admin update...');
                
                $userUpdateResult = DB::table('USER')
                    ->where('user_id', $user->user_id)
                    ->update([
                        'username' => $request->username,
                        'email' => $request->email,
                    ]);

                Log::info('User update result:', ['affected_rows' => $userUpdateResult]);

                $employeeData = [
                    'nama' => $request->nama,
                    'nip' => $request->nip,
                    'posisi' => $request->posisi,
                    'departemen' => $request->departemen,
                    'alamat' => $request->alamat,
                    'no_telepon' => $request->no_telepon,
                ];

                Log::info('Checking hapus_foto value:', ['hapus_foto' => $request->input('hapus_foto')]);
                
                if ($request->input('hapus_foto') == '1') {
                    Log::info('Processing photo deletion for admin...');
                    
                    if ($employee->foto_profil) {
                        $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                        Log::info('Deleting old photo from storage:', ['path' => $oldPath]);
                        $deleted = Storage::disk('public')->delete($oldPath);
                        Log::info('Storage deletion result:', ['deleted' => $deleted]);
                    }
                    
                    $employeeData['foto_profil'] = null;
                    Log::info('Photo marked for deletion in database');
                }

                if ($request->hasFile('foto_profil')) {
                    Log::info('Processing photo upload for admin...');
                    
                    if ($employee->foto_profil && $request->input('hapus_foto') != '1') {
                        $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                        Log::info('Deleting old photo before upload:', ['path' => $oldPath]);
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

                $employeeUpdateResult = DB::table('EMPLOYEE')
                    ->where('emp_id', $employee->emp_id)
                    ->update($employeeData);

                Log::info('Employee update result:', ['affected_rows' => $employeeUpdateResult]);

            } else {
                Log::info('Processing User (Karyawan/Manajemen) update...');
                
                $employee->nama = $request->nama;
                $employee->alamat = $request->alamat;
                $employee->no_telepon = $request->no_telepon;

                Log::info('Checking hapus_foto value for user:', ['hapus_foto' => $request->input('hapus_foto')]);
                
                if ($request->input('hapus_foto') == '1') {
                    Log::info('Processing user photo deletion...');
                    
                    if ($employee->foto_profil) {
                        $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                        Log::info('Deleting user old photo from storage:', ['path' => $oldPath]);
                        $deleted = Storage::disk('public')->delete($oldPath);
                        Log::info('Storage deletion result:', ['deleted' => $deleted]);
                    }
                    
                    $employee->foto_profil = null;
                    Log::info('User photo marked for deletion in database');
                }

                if ($request->hasFile('foto_profil')) {
                    Log::info('Processing user photo upload...');
                    
                    if ($employee->foto_profil && $request->input('hapus_foto') != '1') {
                        $oldPath = str_replace('/storage/', '', $employee->foto_profil);
                        Log::info('Deleting user old photo before upload:', ['path' => $oldPath]);
                        Storage::disk('public')->delete($oldPath);
                    }

                    $file = $request->file('foto_profil');
                    $fileName = $employee->emp_id . '-profil-' . now()->format('YmdHis') . '.' . $file->extension();
                    $path = $file->storeAs('foto_profil', $fileName, 'public');
                    $employee->foto_profil = Storage::url($path);
                    
                    Log::info('User photo stored:', ['path' => $path]);
                }

                $employeeSaved = $employee->save();
                Log::info('User save result:', ['saved' => $employeeSaved]);
            }
            
            DB::commit();
            Log::info('Database transaction committed successfully');

            $successMessage = 'Profil berhasil diperbarui.';
            
            if ($request->input('hapus_foto') == '1') {
                $successMessage .= ' Foto profil telah dihapus.';
            }
            
            Log::info('Update successful: ' . $successMessage);

            if ($role === 'admin') {
                return redirect()->route('admin.profil')->with('success', $successMessage);
            } elseif ($role === 'manajemen') {
                return redirect()->route('manajemen.profil')->with('success', $successMessage);
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

            if ($role === 'admin') {
                return redirect()->route('admin.profil')->with('error', $errorMessage);
            } elseif ($role === 'manajemen') {
                return redirect()->route('manajemen.profil')->with('error', $errorMessage);
            } else {
                return redirect()->route('karyawan.profil')->with('error', $errorMessage);
            }
        } finally {
            Log::info('=== PROFILE UPDATE END ===');
        }
    }

    public function deleteFotoAdmin()
    {
        return redirect()->route('admin.profil')->with('error', 'Method tidak digunakan.');
    }

    public function deleteFotoKaryawan()
    {
        return redirect()->route('karyawan.profil')->with('error', 'Method tidak digunakan.');
    }
}