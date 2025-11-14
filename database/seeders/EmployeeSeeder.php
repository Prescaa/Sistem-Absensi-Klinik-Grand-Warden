<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // <-- Import Model User
use App\Models\Employee; // <-- Import Model Employee
use Illuminate\Support\Facades\DB; // <-- Import DB facade

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Employee::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Cari user 'karyawan' yang baru dibuat
        $karyawanUser = User::where('username', 'karyawan')->first();

        // Jika user-nya ada, buat data employee untuk dia
        if ($karyawanUser) {
            Employee::create([
                'user_id'    => $karyawanUser->user_id,
                'nama'       => 'Mahardika', // Nama ini akan tampil di dashboard
                'nip'        => '123456789',
                'departemen' => 'Teknologi Informasi',
                'posisi'     => 'Frontend Developer',
            ]);
        }
        
        // Anda bisa tambahkan data untuk user admin juga jika mau
    }
}