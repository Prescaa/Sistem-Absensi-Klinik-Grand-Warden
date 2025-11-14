<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
    {
        Schema::create('REPORT', function (Blueprint $table) {
            $table->id('rpt_id');
            $table->unsignedBigInteger('emp_id');
            $table->date('periode');
            $table->integer('total_hadir')->default(0);
            $table->integer('total_tidak_hadir')->default(0);
            $table->string('file_laporan')->nullable();

            $table->foreign('emp_id')->references('emp_id')->on('EMPLOYEE')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('REPORT');
    }
}