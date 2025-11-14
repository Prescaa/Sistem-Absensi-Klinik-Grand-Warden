<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Membuat tabel bernama 'USER' sesuai SQL
        Schema::create('USER', function (Blueprint $table) {
            $table->id('user_id'); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->string('username', 50)->unique();
            $table->char('password_hash', 60);
            $table->string('email', 100)->unique();
            $table->enum('role', ['Admin', 'HRD', 'Karyawan']);
            // Kita tidak menambahkan $table->timestamps(); karena tidak ada di SQL
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('USER');
    }
}