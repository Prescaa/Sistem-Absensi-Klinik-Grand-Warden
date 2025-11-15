{{-- Menggunakan layout admin --}}
@extends('layouts.admin_app')

{{-- Mengatur judul halaman --}}
@section('page-title', 'Admin Dashboard')

{{-- Konten utama halaman dashboard --}}
@section('content')
<div class="container-fluid">

    <!-- Baris untuk Kartu Statistik -->
    <div class="row">

        <!-- Kartu Validasi Pending -->
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <!-- Angka ini nanti akan diisi data dari controller -->
                            <h4 class="card-title mb-0">5</h4>
                            <p class="card-text">Validasi Pending</p>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 opacity-75"></i>
                    </div>
                </div>
                <a href="/admin/validasi" class="card-footer text-white text-decoration-none bg-primary bg-opacity-75">
                    Lihat Detail <i class="bi bi-arrow-right-circle-fill"></i>
                </a>
            </div>
        </div>

        <!-- Kartu Total Karyawan -->
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <!-- Angka ini nanti akan diisi data dari controller -->
                            <h4 class="card-title mb-0">50</h4>
                            <p class="card-text">Total Karyawan</p>
                        </div>
                        <i class="bi bi-people-fill fs-1 opacity-75"></i>
                    </div>
                </div>
                <a href="/admin/manajemen-karyawan" class="card-footer text-white text-decoration-none bg-success bg-opacity-75">
                    Lihat Detail <i class="bi bi-arrow-right-circle-fill"></i>
                </a>
            </div>
        </div>

        <!-- Kartu Kehadiran Hari Ini -->
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <!-- Angka ini nanti akan diisi data dari controller -->
                            <h4 class="card-title mb-0">95.2%</h4>
                            <p class="card-text">Kehadiran Hari Ini</p>
                        </div>
                        <i class="bi bi-pie-chart-fill fs-1 opacity-75"></i>
                    </div>
                </div>
                <a href="/admin/laporan" class="card-footer text-white text-decoration-none bg-info bg-opacity-75">
                    Lihat Detail <i class="bi bi-arrow-right-circle-fill"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Baris untuk Grafik (Placeholder) -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Grafik Kehadiran Mingguan</h5>
                </div>
                <div class="card-body">
                    <p class="text-center text-muted">(Placeholder untuk Chart.js atau library grafik lainnya)</p>
                    <!-- Anda bisa meletakkan elemen <canvas> untuk chart di sini -->
                    <!-- Contoh: <canvas id="attendanceChart" style="width:100%; max-height: 400px;"></canvas> -->
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
