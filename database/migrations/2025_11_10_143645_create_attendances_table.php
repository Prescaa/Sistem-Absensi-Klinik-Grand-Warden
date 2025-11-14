<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
    {
        Schema::create('ATTENDANCE', function (Blueprint $table) {
            $table->id('att_id');
            $table->unsignedBigInteger('emp_id');
            $table->unsignedBigInteger('area_id')->nullable(); 
            $table->dateTime('waktu_unggah');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('nama_file_foto')->nullable();
            $table->dateTime('timestamp_ekstraksi')->nullable();

            $table->foreign('emp_id')->references('emp_id')->on('EMPLOYEE')->onDelete('cascade');
            $table->foreign('area_id')->references('area_id')->on('WORK_AREA')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ATTENDANCE');
    }
}