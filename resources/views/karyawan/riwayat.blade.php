@extends('layouts.app')

@section('page-title', 'Riwayat Absensi')

@section('content')
    <div class="d-flex flex-column h-100">
    
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                <i class="bi bi-person-fill text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted small mb-1">Nama Karyawan</h6>
                                <h5 class="fw-bold mb-0">Mahardika</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                <i class="bi bi-clock-fill text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted small mb-1">Jam</h6>
                                <h5 class="fw-bold mb-0" id="current-time">--:--</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                <i class="bi bi-calendar-fill text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted small mb-1">Tanggal</h6>
                                <h5 class="fw-bold mb-0" id="current-date">...</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                <i class="bi bi-geo-alt-fill text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted small mb-1">Lokasi</h6>
                                <h5 class="fw-bold mb-0">Jl. Medan Merdeka T.</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <div class="row flex-grow-1">
            <div class="col-lg-8 d-flex flex-column">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <h6 class="text-muted small">Cuti</h6>
                                <h5 class="fw-bold">10 Hari</h5>
                            </div>
                            <div class="col-4">
                                <h6 class="text-muted small">Izin</h6>
                                <h5 class="fw-bold">2 Hari</h5>
                            </div>
                            <div class="col-4">
                                <h6 class="text-muted small">Sakit</h6>
                                <h5 class="fw-bold">- Hari</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3 fw-bold">Riwayat Absensi</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0"><strong>19-28 Januari 2025</strong> - Cuti</li>
                            <li class="list-group-item px-0"><strong>18 Januari 2025</strong> - Acara keluarga</li>
                            <li class="list-group-item px-0"><strong>02 Januari 2025</strong> - Mengantar anak ke rumah sakit</li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm border-0 flex-grow-1">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3 fw-bold">Status Kehadiran (Unggah Foto)</h5>
                        
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <strong>11 April 2025</strong> - Waktu: 08:15:20
                                </div>
                                <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                            </li>
                            
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <strong>10 April 2025</strong> - Waktu: 08:12:00
                                </div>
                                <span class="badge bg-primary">Hadir</span>
                            </li>
                            
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <strong>09 April 2025</strong> - Waktu: 09:10:00
                                </div>
                                <span class="badge bg-primary">Hadir</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 d-flex">
                <div class="card shadow-sm border-0 w-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button class="btn btn-light btn-sm"><i class="bi bi-chevron-left"></i></button>
                            <h6 class="mb-0 fw-bold">Rekap Bulan</h6>
                            <button class="btn btn-light btn-sm"><i class="bi bi-chevron-right"></i></button>
                        </div>
                        <h5 class="fw-bold text-center mb-3">Januari</h5>
                        <div>
                            <canvas id="rekapChart"></canvas>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3 fw-bold">Tingkat Kehadiran</h5>
                        <div class="list-group list-group-flush" id="tingkatKehadiranList">
                            <div class="list-group-item px-0 d-flex justify-content-between">
                                <span>Total Hari Kerja</span> <span class="fw-bold">45</span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between">
                                <span>Total Kehadiran</span> <span class="fw-bold">30</span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between">
                                <span>Total Terlambat</span> <span class="fw-bold">2</span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between">
                                <span>Total Absen</span> <span class="fw-bold">15</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    
    </div> @endsection

@push('styles')
<style>
    /* Mengatur card agar memiliki border-radius yang konsisten */
    .card {
        border-radius: 12px;
    }
    #tingkatKehadiranList .list-group-item {
        font-size: 0.9rem;
        padding-top: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0 !important;
    }
    #tingkatKehadiranList .list-group-item:last-child {
        border-bottom: none !important;
    }
    #tingkatKehadiranList .list-group-item span:first-child {
        color: #6c757d;
    }
    /* Memastikan list di card lain juga rapi */
    .list-group-item {
        border-bottom: 1px solid #f0f0f0 !important;
    }
    .list-group-item:last-child {
        border-bottom: none !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. Script untuk Jam & Tanggal Real-time ---
        function updateDateTime() {
            const now = new Date();
            const timeEl = document.getElementById('current-time');
            const dateEl = document.getElementById('current-date');
            
            if (timeEl) {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                timeEl.textContent = `${hours}:${minutes}`;
            }
            
            if (dateEl) {
                const options = { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' };
                const dateStr = now.toLocaleDateString('id-ID', options).replace('.', ',');
                dateEl.textContent = dateStr;
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 60000); // Update setiap menit

        // --- 2. Script untuk Chart Dinamis ---
        const ctx = document.getElementById('rekapChart');
        
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['M1', 'M2', 'M3', 'M4'],
                    datasets: [{
                        label: 'Kehadiran',
                        data: [18, 20, 15, 19], 
                        backgroundColor: '#0d6efd',
                        borderRadius: 5,
                        barThickness: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { display: false },
                            grid: { display: false }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush