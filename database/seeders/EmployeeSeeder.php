<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Employee::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // --- DATA UNTUK KARYAWAN (Mahardika) ---
        $karyawanUser = User::where('username', 'karyawan')->first();

        if ($karyawanUser) {
            Employee::create([
                'user_id'       => $karyawanUser->user_id,
                'nama'          => 'Mahardika',
                'nip'           => '123456789',
                'departemen'    => 'Teknologi Informasi',
                'posisi'        => 'Frontend Developer',
            ]);

            // HAPUS BARIS-BARIS INI:
            // $karyawanUser->emp_id = $employeeKaryawan->emp_id;
            // $karyawanUser->save();
        }

        // --- DATA UNTUK ADMIN (Sahroni) ---
        $adminUser = User::where('username', 'admin')->first();

        if ($adminUser) {
            Employee::create([
                'user_id'       => $adminUser->user_id,
                'nama'          => 'Sahroni',
                'nip'           => '987654321',
                'departemen'    => 'Manajemen',
                'posisi'        => 'Administrator',
            ]);

            // HAPUS BARIS-BARIS INI:
            // $adminUser->emp_id = $employeeAdmin->emp_id;
            // $adminUser->save();
        }
    }
}
