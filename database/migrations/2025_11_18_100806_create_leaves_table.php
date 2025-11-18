<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id('leave_id');
            // Menghubungkan dengan emp_id di tabel employees
            $table->unsignedBigInteger('emp_id');

            // Jenis izin: Sakit, Cuti, atau Izin (Keperluan Lain)
            $table->enum('tipe_izin', ['sakit', 'izin', 'cuti']);

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('deskripsi')->nullable();

            // Path file bukti (misal surat dokter), nullable
            $table->string('file_bukti')->nullable();

            // Status persetujuan: pending, disetujui, ditolak
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');

            // Catatan dari admin jika ditolak/disetujui
            $table->text('catatan_admin')->nullable();

            $table->timestamps();

            // Foreign key constraint (opsional, sesuaikan jika tabel employees ada)
            // $table->foreign('emp_id')->references('emp_id')->on('employees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leaves');
    }
};
