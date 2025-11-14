<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('EMPLOYEE', function (Blueprint $table) {
            $table->id('emp_id');
            // Ganti 'foreignId' dengan 'unsignedInteger' agar sesuai dengan 'id()'
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('nama', 150);
            $table->string('nip', 20)->unique()->nullable();
            $table->string('departemen', 100)->nullable();
            $table->string('posisi', 100)->nullable();
            $table->boolean('status_aktif')->default(true);

            // Membuat Foreign Key
            $table->foreign('user_id')->references('user_id')->on('USER')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('EMPLOYEE');
    }
}