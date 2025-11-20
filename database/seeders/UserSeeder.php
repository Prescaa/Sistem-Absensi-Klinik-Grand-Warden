<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Matikan check foreign key biar bisa truncate (kosongkan) tabel
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); 
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. User Karyawan (Mahardika)
        User::create([
            'username'      => 'karyawan',
            'password_hash' => Hash::make('12345'), 
            'email'         => 'karyawan@kgh.com',
            'role'          => 'Karyawan'
        ]);

        // 2. User Admin (Sahroni)
        User::create([
            'username'      => 'admin',
            'password_hash' => Hash::make('admin123'), 
            'email'         => 'admin@kgh.com',
            'role'          => 'Admin'
        ]);

        // 3. User Manajemen (Ahmad) - DATA BARU
        User::create([
            'username'      => 'manajemen',
            'password_hash' => Hash::make('12345678'), 
            'email'         => 'manajer@kgh.com',
            'role'          => 'Manajemen' // Sesuai Enum di Database
        ]);
    }
}