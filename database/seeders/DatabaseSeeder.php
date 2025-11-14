<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Panggil UserSeeder dulu, baru EmployeeSeeder
        $this->call([
            UserSeeder::class,
            EmployeeSeeder::class,
            // Anda bisa tambahkan seeder lain di sini
        ]);
    }
}