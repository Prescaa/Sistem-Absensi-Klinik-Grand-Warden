<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateValidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('VALIDATION', function (Blueprint $table) {
            $table->id('val_id');
            $table->unsignedBigInteger('att_id')->unique();
            $table->enum('status_validasi_otomatis', ['Valid', 'Invalid', 'Need Review']);
            $table->enum('status_validasi_final', ['Valid', 'Invalid', 'Pending']);
            $table->unsignedInteger('admin_id')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->dateTime('timestamp_validasi');

            $table->foreign('att_id')->references('att_id')->on('ATTENDANCE')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('VALIDATION');
    }
}