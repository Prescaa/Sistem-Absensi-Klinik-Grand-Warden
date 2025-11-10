@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<div class="dashboard-container">
    <div class="row g-4">
        <!-- Kolom Kiri - Status Validasi -->
        <div class="col-lg-8">
            <!-- Card Status Validasi -->
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3 fw-bold">Status Validasi Anda</h5>
                    <div class="list-group list-group-flush">
                        
                        <!-- Item 1 -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-0 border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-3">
                                    <i class="bi bi-calendar-check text-primary"></i>
                                </div>
                                <div>
                                    <strong>11 April 2025</strong>
                                    <div class="text-muted small">Absensi Kehadiran</div>
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                        </div>
                        
                        <!-- Item 2 -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-0 border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-3">
                                    <i class="bi bi-calendar-check text-primary"></i>
                                </div>
                                <div>
                                    <strong>19-28 Januari 2025</strong>
                                    <div class="text-muted small">Cuti</div>
                                </div>
                            </div>
                            <span class="badge bg-primary">Diterima</span>
                        </div>
                        
                        <!-- Item 3 -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-0 border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-3">
                                    <i class="bi bi-calendar-check text-primary"></i>
                                </div>
                                <div>
                                    <strong>18 Januari 2025</strong>
                                    <div class="text-muted small">Acara keluarga</div>
                                </div>
                            </div>
                            <span class="badge bg-primary">Diterima</span>
                        </div>
                        
                        <!-- Item 4 -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-0">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-3">
                                    <i class="bi bi-calendar-check text-primary"></i>
                                </div>
                                <div>
                                    <strong>02 Januari 2025</strong>
                                    <div class="text-muted small">Mengantar anak ke rumah sakit</div>
                                </div>
                            </div>
                            <span class="badge bg-primary">Diterima</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan - Summary -->
        <div class="col-lg-4">
            <!-- Card Summary -->
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <h5 class="card-title mb-3 fw-bold">Summary</h5>
                    
                    <div class="flex-grow-1">
                        <!-- Jam -->
                        <div class="card summary-inner-card border-0 mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 summary-icon">
                                        <i class="bi bi-clock-fill text-white"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-1 small summary-label">Jam</h6>
                                        <h4 class="fw-bold mb-0 summary-value" id="realtime-jam">--:--</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tanggal -->
                        <div class="card summary-inner-card border-0 mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 summary-icon">
                                        <i class="bi bi-calendar-fill text-white"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-1 small summary-label">Tanggal</h6>
                                        <h4 class="fw-bold mb-0 summary-value" id="realtime-tanggal">...</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Catatan -->
                        <div class="card summary-inner-card border-0 flex-grow-1">
                            <div class="card-body p-3 d-flex flex-column h-100">
                                <h6 class="fw-bold mb-2">Catatan</h6>
                                <textarea class="form-control catatan-textarea flex-grow-1" placeholder="Tulis catatan Anda..." style="resize: none;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Absensi Tiga Bulan Terakhir -->
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-4">Absensi Tiga Bulan Terakhir</h4>
            
            <div class="row justify-content-center">
                <!-- Card Cuti -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6 mb-3">
                    <div class="card shadow-sm border-0 h-100 text-center stats-card">
                        <div class="card-body p-3">
                            <div class="bg-info rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-airplane-fill text-white fs-6"></i>
                            </div>
                            <h6 class="text-muted mb-1 small">Cuti</h6>
                            <h5 class="fw-bold mb-0">10</h5>
                            <small class="text-muted">Hari</small>
                        </div>
                    </div>
                </div>
                
                <!-- Card Izin -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6 mb-3">
                    <div class="card shadow-sm border-0 h-100 text-center stats-card">
                        <div class="card-body p-3">
                            <div class="bg-warning rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-calendar-check-fill text-white fs-6"></i>
                            </div>
                            <h6 class="text-muted mb-1 small">Izin</h6>
                            <h5 class="fw-bold mb-0">5</h5>
                            <small class="text-muted">Hari</small>
                        </div>
                    </div>
                </div>
                
                <!-- Card Sakit -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6 mb-3">
                    <div class="card shadow-sm border-0 h-100 text-center stats-card">
                        <div class="card-body p-3">
                            <div class="bg-danger rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-heart-pulse-fill text-white fs-6"></i>
                            </div>
                            <h6 class="text-muted mb-1 small">Sakit</h6>
                            <h5 class="fw-bold mb-0">2</h5>
                            <small class="text-muted">Hari</small>
                        </div>
                    </div>
                </div>
                
                <!-- Card Hari Libur -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6 mb-3">
                    <div class="card shadow-sm border-0 h-100 text-center stats-card">
                        <div class="card-body p-3">
                            <div class="bg-secondary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-umbrella-fill text-white fs-6"></i>
                            </div>
                            <h6 class="text-muted mb-1 small">Hari Libur</h6>
                            <h5 class="fw-bold mb-0">7</h5>
                            <small class="text-muted">Hari</small>
                        </div>
                    </div>
                </div>

                <!-- Card Hadir -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6 mb-3">
                    <div class="card shadow-sm border-0 h-100 text-center stats-card">
                        <div class="card-body p-3">
                            <div class="bg-success rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-check-circle-fill text-white fs-6"></i>
                            </div>
                            <h6 class="text-muted mb-1 small">Hadir</h6>
                            <h5 class="fw-bold mb-0">45</h5>
                            <small class="text-muted">Hari</small>
                        </div>
                    </div>
                </div>

                <!-- Card Terlambat -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6 mb-3">
                    <div class="card shadow-sm border-0 h-100 text-center stats-card">
                        <div class="card-body p-3">
                            <div class="bg-primary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-clock-fill text-white fs-6"></i>
                            </div>
                            <h6 class="text-muted mb-1 small">Terlambat</h6>
                            <h5 class="fw-bold mb-0">3</h5>
                            <small class="text-muted">Hari</small>
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
        transition: all 0.3s ease;
    }
    .list-group-item {
        border-bottom: 1px solid #f0f0f0 !important;
        transition: all 0.3s ease;
    }
    .list-group-item:last-child {
        border-bottom: none !important;
    }
    .bg-light {
        background-color: #f8f9fa !important;
        transition: background-color 0.3s ease;
    }
    
    /* Dashboard container */
    .dashboard-container {
        min-height: calc(100vh - 180px);
        padding-bottom: 20px;
    }
    
    /* Summary inner cards */
    .summary-inner-card {
        background-color: #f8f9fa !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    /* Summary icon */
    .summary-icon {
        width: 40px !important;
        height: 40px !important;
    }
    
    /* Summary value (Jam & Tanggal) */
    .summary-value {
        font-size: 1.1rem !important;
        line-height: 1.2;
        margin-bottom: 0 !important;
    }
    
    /* Summary label */
    .summary-label {
        font-size: 0.8rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    /* Catatan textarea */
    .catatan-textarea {
        background-color: #ffffff !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px;
        color: #212529;
        transition: all 0.3s ease;
        min-height: 100px;
    }
    
    .catatan-textarea:focus {
        border-color: #86b7fe !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
    }
    
    .catatan-textarea::placeholder {
        color: #6c757d;
    }

    /* Dark Mode Styles */
    .dark-mode .card {
        background-color: #2d2d2d;
        border-color: #444;
        color: #ffffff;
    }

    .dark-mode .list-group-item {
        border-bottom-color: #444 !important;
        background-color: #2d2d2d;
        color: #ffffff;
    }

    .dark-mode .bg-light {
        background-color: #3d3d3d !important;
    }

    .dark-mode .text-muted {
        color: #adb5bd !important;
    }

    /* Summary cards in dark mode */
    .dark-mode .summary-inner-card {
        background-color: #3d3d3d !important;
        border-color: #555;
        color: #ffffff;
    }

    /* Catatan textarea in dark mode */
    .dark-mode .catatan-textarea {
        background-color: #2d2d2d !important;
        border-color: #555 !important;
        color: #ffffff !important;
    }

    .dark-mode .catatan-textarea:focus {
        border-color: #86b7fe !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        background-color: #2d2d2d !important;
        color: #ffffff !important;
    }

    .dark-mode .catatan-textarea::placeholder {
        color: #adb5bd !important;
    }

    /* Stats cards in dark mode */
    .dark-mode .stats-card {
        background-color: #2d2d2d;
        border-color: #444;
    }

    /* Badge colors in dark mode */
    .dark-mode .badge.bg-warning {
        background-color: #ffc107 !important;
        color: #000 !important;
    }

    .dark-mode .badge.bg-primary {
        background-color: #0d6efd !important;
        color: #fff !important;
    }

    /* Ensure both cards have same height */
    .row.g-4 > .col-lg-8,
    .row.g-4 > .col-lg-4 {
        display: flex;
    }

    .row.g-4 > .col-lg-8 .card,
    .row.g-4 > .col-lg-4 .card {
        flex: 1;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-container {
            min-height: auto;
        }
        .row.g-4 > .col-lg-8,
        .row.g-4 > .col-lg-4 {
            display: block;
        }
        .summary-value {
            font-size: 1rem !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function updateDateTime() {
        const jamElement = document.getElementById('realtime-jam');
        const tanggalElement = document.getElementById('realtime-tanggal');
        
        if (jamElement && tanggalElement) {
            const now = new Date();
            
            // Format jam
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            jamElement.textContent = `${hours}:${minutes}`;
            
            // Format tanggal (e.g., Jum, 7 November 2025)
            const options = { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' };
            let tanggalStr = now.toLocaleDateString('id-ID', options).replace('.', ','); 
            tanggalElement.textContent = tanggalStr;
        }
    }
    
    // Update setiap detik
    setInterval(updateDateTime, 1000);
    
    // Jalankan sekali saat halaman dimuat
    updateDateTime();
</script>
@endpush