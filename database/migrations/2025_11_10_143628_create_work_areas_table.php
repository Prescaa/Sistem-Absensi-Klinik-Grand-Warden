<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('WORK_AREA', function (Blueprint $table) {
            $table->id('area_id');
            $table->string('nama_area', 100);
            $table->point('koordinat_pusat'); // Kolom POINT
            $table->integer('radius_geofence');
            $table->json('jam_kerja')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('WORK_AREA');
    }
}