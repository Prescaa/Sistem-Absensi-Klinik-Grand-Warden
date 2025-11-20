{{-- Menggunakan layout admin --}}
@extends('layouts.admin_app')

{{-- Mengatur judul halaman --}}
@section('page-title', 'Dashboard')

{{-- Konten utama halaman dashboard --}}
@section('content')

<div class="d-flex flex-column h-100">

    <div class="row flex-grow-1">
        
        <div class="col-lg-6 d-flex flex-column">
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card card-stat bg-success-soft border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-success-dark small fw-bold mb-1">Jumlah Karyawan</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $totalEmployees }}</h3>
                                </div>
                                <div class="icon-box bg-success text-white rounded-circle">
                                    <i class="bi bi-people-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-stat bg-primary-soft border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-primary-dark small fw-bold mb-1">Hadir Hari Ini</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $presentCount }}</h3>
                                </div>
                                <div class="icon-box bg-primary text-white rounded-circle">
                                    <i class="bi bi-person-check-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-stat bg-warning-soft border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-warning-dark small fw-bold mb-1">Izin</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $izinCount }}</h3>
                                </div>
                                <div class="icon-box bg-warning text-white rounded-circle">
                                    <i class="bi bi-calendar-check-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-stat bg-danger-soft border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-danger-dark small fw-bold mb-1">Sakit</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $sakitCount }}</h3>
                                </div>
                                <div class="icon-box bg-danger text-white rounded-circle">
                                    <i class="bi bi-heart-pulse-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
            
            <div class="card shadow-sm border-0 mb-4 flex-grow-1">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <h5 class="card-title fw-bold mb-0 me-2">Menunggu Validasi Terbaru</h5>
                        @if($recentActivities->count() > 0)
                            <span class="badge rounded-pill bg-danger">{{ $recentActivities->count() }}</span>
                        @endif
                    </div>
                    
                    <div class="list-group list-group-flush">
                        @forelse($recentActivities as $act)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-bottom">
                                <div>
                                    <span class="fw-bold text-body">{{ $act->employee->nama ?? 'Karyawan' }}</span>
                                    <div class="text-muted small mt-1">
                                        <i class="bi bi-clock me-1"></i> {{ \Carbon\Carbon::parse($act->waktu_unggah)->format('d M Y, H:i') }} 
                                        &mdash; <span class="text-primary fw-bold">Absen {{ ucfirst($act->type) }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('admin.validasi.show') }}" class="btn btn-sm btn-outline-primary">Review</a>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle-fill fs-1 mb-3 d-block text-success opacity-50"></i>
                                Tidak ada absensi yang menunggu validasi saat ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 d-flex flex-column">
            
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body text-center p-4 d-flex flex-column justify-content-center">
                                    <small class="text-muted mb-1">Jam Saat Ini</small>
                                    <h2 class="fw-bold mb-0 tracking-wider text-body" id="realtime-jam">--:--:--</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body text-center p-4 d-flex flex-column justify-content-center">
                                    <small class="text-muted mb-1">Tanggal Hari Ini</small>
                                    <h5 class="fw-bold mb-0 text-body" id="realtime-tanggal">...</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 mb-4 flex-grow-1">
                <div class="card-body p-5 d-flex flex-column justify-content-center align-items-center text-center">
                    <div class="mb-4 p-4 bg-success bg-opacity-10 rounded-circle">
                        <i class="bi bi-file-earmark-spreadsheet-fill text-success display-1"></i>
                    </div>
                    
                    <h4 class="fw-bold mb-2 text-body">Download Laporan Absensi</h4>
                    <p class="text-muted mb-4" style="max-width: 400px;">
                        Pilih rentang tanggal untuk mengunduh rekapitulasi absensi karyawan dalam format CSV/Excel.
                    </p>

                    <button class="btn btn-success btn-lg px-5 py-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="bi bi-download me-2"></i> Pilih Tanggal & Download
                    </button>
                </div>
            </div>
        </div>
        
    </div>
</div>

<div class="modal fade" id="exportModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Export Data Laporan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('admin.laporan.export') }}" method="POST">
          @csrf
          <div class="modal-body">
              <div class="mb-3">
                  <label class="form-label fw-bold">Dari Tanggal</label>
                  <input type="date" name="start_date" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label fw-bold">Sampai Tanggal</label>
                  <input type="date" name="end_date" class="form-control" required>
              </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4">Download CSV</button>
          </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
    /* === WARNA KARTU SOFT (LIGHT MODE) === */
    .bg-success-soft { background-color: #d1e7dd; }
    .text-success-dark { color: #0f5132; }
    
    .bg-primary-soft { background-color: #cfe2ff; }
    .text-primary-dark { color: #084298; }
    
    .bg-warning-soft { background-color: #fff3cd; }
    .text-warning-dark { color: #664d03; }
    
    .bg-danger-soft { background-color: #f8d7da; }
    .text-danger-dark { color: #842029; }

    .icon-box {
        width: 48px; height: 48px;
        display: flex; align-items: center; justify-content: center;
    }
    
    .card-stat {
        transition: transform 0.2s;
        border-radius: 12px;
    }
    .card-stat:hover { transform: translateY(-3px); }

    /* === DARK MODE OVERRIDES (PERBAIKAN WARNA AGAR TIDAK NGEJRENG) === */
    .dark-mode .bg-success-soft { background-color: #052c1e !important; border: 1px solid #0f5132; }
    .dark-mode .text-success-dark { color: #75b798 !important; }
    
    .dark-mode .bg-primary-soft { background-color: #031633 !important; border: 1px solid #084298; }
    .dark-mode .text-primary-dark { color: #6ea8fe !important; }
    
    .dark-mode .bg-warning-soft { background-color: #332701 !important; border: 1px solid #664d03; }
    .dark-mode .text-warning-dark { color: #ffda6a !important; }
    
    .dark-mode .bg-danger-soft { background-color: #2c0b0e !important; border: 1px solid #842029; }
    .dark-mode .text-danger-dark { color: #ea868f !important; }

    /* Teks standar di dark mode */
    .dark-mode .text-dark-emphasis { color: #e0e0e0 !important; }
    .dark-mode .text-body { color: #e0e0e0 !important; }
    
    /* Kartu Umum di Dark Mode */
    .dark-mode .card { background-color: #1e1e1e !important; border: 1px solid #333; }
    
    /* Kartu Laporan di Dark Mode */
    .dark-mode .bg-success.bg-opacity-10 { 
        background-color: rgba(25, 135, 84, 0.2) !important; 
    }
    
    /* Modal di Dark Mode */
    .dark-mode .modal-content { background-color: #1e1e1e; color: #fff; }
    .dark-mode .modal-footer.bg-light { background-color: #252525 !important; }
    .dark-mode .btn-close { filter: invert(1); }
    .dark-mode .form-control { background-color: #2b2b2b; border-color: #444; color: #fff; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- SKRIP JAM ---
        function updateClock() {
            const timeEl = document.getElementById('realtime-jam');
            const dateEl = document.getElementById('realtime-tanggal');
            
            const now = new Date();
            
            if (timeEl) {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                timeEl.textContent = `${hours}:${minutes}:${seconds}`;
            }

            if (dateEl) {
                const options = { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' };
                dateEl.textContent = now.toLocaleDateString('id-ID', options);
            }
        }

        updateClock();
        setInterval(updateClock, 1000);
    });
</script>
@endpush