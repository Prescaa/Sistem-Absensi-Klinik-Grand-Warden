<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); 
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Buat user karyawan
        User::create([
            'username'      => 'karyawan',
            'password_hash' => Hash::make('12345'), // Password adalah '101010'
            'email'         => 'karyawan@kgh.com',
            'role'          => 'Karyawan'
        ]);

        // Buat user admin (opsional, tapi bagus untuk nanti)
        User::create([
            'username'      => 'admin',
            'password_hash' => Hash::make('admin123'), // Password adalah 'admin123'
            'email'         => 'admin@kgh.com',
            'role'          => 'Admin'
        ]);
    }
}