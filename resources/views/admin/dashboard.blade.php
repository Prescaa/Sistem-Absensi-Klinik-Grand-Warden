{{-- Menggunakan layout admin --}}
@extends('layouts.admin_app')

{{-- Mengatur judul halaman --}}
@section('page-title', 'Dashboard')

{{-- Konten utama halaman dashboard --}}
@section('content')
<div class="d-flex flex-column h-100">
    <div class="row flex-grow-1">
        
        <div class="col-lg-7 d-flex flex-column">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card text-white bg-success shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-75 small">Jumlah Karyawan</h6>
                                    <h3 class="fw-bold mb-0">125</h3>
                                </div>
                                <i class="bi bi-people-fill fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card text-white bg-primary shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-75 small">Hadir</h6>
                                    <h3 class="fw-bold mb-0">89</h3>
                                </div>
                                <i class="bi bi-person-check-fill fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card text-dark bg-warning shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-dark-75 small">Izin</h6>
                                    <h3 class="fw-bold mb-0">15</h3>
                                </div>
                                <i class="bi bi-calendar-check-fill fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card text-white bg-danger shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-75 small">Sakit</h6>
                                    <h3 class="fw-bold mb-0">10</h3>
                                </div>
                                <i class="bi bi-heart-pulse-fill fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 flex-grow-1">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <h5 class="card-title fw-bold mb-0 me-2">Menunggu Persetujuan</h5>
                        <span class="badge rounded-pill bg-danger">1</span>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong>11 April 2025</strong> - Mahardika
                                <div class="text-muted small">Waktu: 08:15:20 - Izin</div>
                            </div>
                            <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                        </div>
                        
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                             <div>
                                <strong>11 April 2025</strong> - Joko
                                <div class="text-muted small">Waktu: 08:12:00 - Sakit</div>
                            </div>
                            <span class="badge bg-primary">Valid</span>
                        </div>
                        
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                             <div>
                                <strong>11 April 2025</strong> - Tom
                                <div class="text-muted small">Waktu: 09:10:00 - Presensi Kehadiran</div>
                            </div>
                            <span class="badge bg-danger">Terlambat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 d-flex flex-column">
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <h6 class="text-muted">Jam</h6>
                            <h3 class="fw-bold" id="realtime-jam">--:--</h3>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <h6 class="text-muted">Tanggal</h6>
                            <h3 class="fw-bold" id="realtime-tanggal">...</h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold">Ekspor Data Absensi</h5>
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-download me-2"></i> Ekspor Data
                    </button>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 flex-grow-1">
                <div class="card-body p-4 d-flex flex-column">
                    <h5 class="card-title mb-3 fw-bold">Tingkat Kehadiran</h5>
                    
                    <div class="flex-grow-1">
                        <div class="row h-100">
                            <div class="col-md-5 d-flex flex-column justify-content-center">
                                <h1 class="fw-bold mb-0">85%</h1>
                                
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <button class="btn btn-light btn-sm" id="prevMonthBtn">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <span class="text-muted fw-bold" id="chart-month-year">Januari 2025</span>
                                    <button class="btn btn-light btn-sm" id="nextMonthBtn">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-7 d-flex align-items-center">
                                <div style="width: 100%; height: 150px;">
                                    <canvas id="adminAttendanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        border-radius: 12px;
    }
    .text-white-75 { color: rgba(255, 255, 255, 0.75) !important; }
    .text-dark-75 { color: rgba(0, 0, 0, 0.75) !important; }

    .card.bg-primary, .card.bg-success, .card.bg-info, .card.bg-warning, .card.bg-danger {
        border-radius: .75rem; 
    }
    
    .list-group-item {
        border-bottom: 1px solid #f0f0f0 !important;
    }
    .list-group-item:last-child {
        border-bottom: none !important;
    }
    #realtime-tanggal {
        font-size: 1.2rem;
    }
    #chart-month-year {
        font-size: 0.95rem;
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. Script untuk Jam & Tanggal Real-time ---
        function updateDateTime() {
            const jamElement = document.getElementById('realtime-jam');
            const tanggalElement = document.getElementById('realtime-tanggal');
            
            if (jamElement && tanggalElement) {
                const now = new Date();
                
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                jamElement.textContent = `${hours}:${minutes}`;
                
                const options = { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' };
                let tanggalStr = now.toLocaleDateString('id-ID', options).replace('.', ',');
                tanggalElement.textContent = tanggalStr;
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 1000); // Update setiap detik

        // --- 2. Script untuk Chart Kehadiran (statis) ---
        const ctx = document.getElementById('adminAttendanceChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['M1', 'M2', 'M3', 'M4'],
                    datasets: [{
                        label: 'Kehadiran',
                        data: [18, 20, 15, 19], // Data dummy
                        backgroundColor: '#0d6efd',
                        borderRadius: 5,
                        barThickness: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            ticks: { display: false },
                            grid: { display: false }
                        },
                        x: {
                            ticks: { color: '#6c757d' },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // --- 3. Script Navigasi Bulan (Statis) ---
        const prevMonthBtn = document.getElementById('prevMonthBtn');
        const nextMonthBtn = document.getElementById('nextMonthBtn');
        const monthYearLabel = document.getElementById('chart-month-year');
        
        let currentChartDate = new Date('2025-01-01');

        function updateMonthLabel() {
            const options = { month: 'long', year: 'numeric' };
            monthYearLabel.textContent = currentChartDate.toLocaleDateString('id-ID', options);
        }

        if(nextMonthBtn) {
            nextMonthBtn.addEventListener('click', function() {
                currentChartDate.setMonth(currentChartDate.getMonth() + 1);
                updateMonthLabel();
            });
        }

        if(prevMonthBtn) {
            prevMonthBtn.addEventListener('click', function() {
                currentChartDate.setMonth(currentChartDate.getMonth() - 1);
                updateMonthLabel();
            });
        }
    });
</script>
@endpush