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

        // 1. DATA KARYAWAN (Mahardika)
        $karyawanUser = User::where('username', 'karyawan')->first();
        if ($karyawanUser) {
            Employee::create([
                'user_id'       => $karyawanUser->user_id,
                'nama'          => 'Mahardika',
                'nip'           => '123456789',
                'departemen'    => 'Teknologi Informasi',
                'posisi'        => 'Frontend Developer',
            ]);
        }

        // 2. DATA ADMIN (Sahroni)
        $adminUser = User::where('username', 'admin')->first();
        if ($adminUser) {
            Employee::create([
                'user_id'       => $adminUser->user_id,
                'nama'          => 'Sahroni',
                'nip'           => '987654321',
                'departemen'    => 'Manajemen', // Departemen Admin
                'posisi'        => 'Administrator',
            ]);
        }

         // 3. SUPER ADMIN (Erika)
        $adminUser = User::where('username', 'admin')->first();
        if ($adminUser) {
            Employee::create([
                'user_id'       => $adminUser->user_id,
                'nama'          => 'Erika',
                'nip'           => '987655321',
                'departemen'    => 'Manajemen', // Departemen Admin
                'posisi'        => 'Administrator',
            ]);
        }

        // 4. DATA MANAJEMEN (Ahmad) - DATA BARU
        $manajemenUser = User::where('username', 'manajemen')->first();
        if ($manajemenUser) {
            Employee::create([
                'user_id'       => $manajemenUser->user_id,
                'nama'          => 'Ahmad',
                'nip'           => '1234567890',
                'departemen'    => 'Eksekutif',
                'posisi'        => 'Manajer',
            ]);
        }
    }
}