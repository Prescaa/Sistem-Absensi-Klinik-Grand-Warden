<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    public function up()
    {
        Schema::create('EMPLOYEE', function (Blueprint $table) {
            $table->id('emp_id');
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('nama', 150);
            $table->string('nip', 20)->unique()->nullable();
            $table->string('departemen', 100)->nullable();
            $table->string('posisi', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->string('foto_profil')->nullable();
            $table->boolean('status_aktif')->default(true);

            $table->foreign('user_id')->references('user_id')->on('USER')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('EMPLOYEE');
    }
}