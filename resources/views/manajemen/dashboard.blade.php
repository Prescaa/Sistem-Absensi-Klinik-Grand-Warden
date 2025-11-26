{{-- Menggunakan layout khusus Manajemen --}}
@extends('layouts.manajemen_app')

{{-- Mengatur judul halaman --}}
@section('page-title', 'Dashboard Manajemen')

{{-- Konten utama halaman dashboard --}}
@section('content')

<div class="d-flex flex-column h-100">

    <div class="row flex-grow-1">

        {{-- KOLOM KIRI: STATISTIK & GRAFIK ANALISIS --}}
        <div class="col-lg-8 d-flex flex-column">

            {{-- Baris Kartu Statistik --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-stat bg-success-soft border-0 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-success-dark small fw-bold mb-1">Karyawan</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $totalEmployees }}</h3>
                                </div>
                                <i class="bi bi-people-fill fs-3 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-stat bg-primary-soft border-0 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-primary-dark small fw-bold mb-1">Hadir</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $presentCount }}</h3>
                                </div>
                                <i class="bi bi-person-check-fill fs-3 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-stat bg-warning-soft border-0 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-warning-dark small fw-bold mb-1">Izin</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $izinCount }}</h3>
                                </div>
                                <i class="bi bi-calendar-check-fill fs-3 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-stat bg-danger-soft border-0 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-danger-dark small fw-bold mb-1">Sakit</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $sakitCount }}</h3>
                                </div>
                                <i class="bi bi-heart-pulse-fill fs-3 text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FITUR BARU: GRAFIK TREN KEHADIRAN (Placeholder Analisis) --}}
            <div class="card shadow-sm border-0 mb-4 flex-grow-1">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark-emphasis">Tren Kehadiran Minggu Ini</h5>
                    <small class="text-muted">Analisis Produktivitas</small>
                </div>
                <div class="card-body p-4 d-flex align-items-end justify-content-around" style="height: 300px;">
                    {{-- Visualisasi Grafik Batang Sederhana dengan CSS --}}
                    @foreach($chartData as $index => $data)
                        <div class="text-center w-100">
                            @php
                                $height = $totalEmployees > 0 ? ($data / $totalEmployees) * 200 : 0;
                                $colorClass = $data >= ($totalEmployees * 0.8) ? 'bg-primary' : ($data >= ($totalEmployees * 0.5) ? 'bg-warning' : 'bg-danger');
                            @endphp

                            <div class="mx-auto rounded-top {{ $colorClass }}"
                                 style="width: 40px; height: {{ $height }}px; transition: height 1s ease;"
                                 title="{{ $data }} Karyawan">
                            </div>
                            <small class="d-block mt-2 fw-bold text-muted" style="font-size: 0.75rem;">{{ $chartLabels[$index] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: JAM & EXPORT LAPORAN --}}
        <div class="col-lg-4 d-flex flex-column">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center p-4">
                    <h6 class="text-muted mb-1">Waktu Server</h6>
                    <h2 class="fw-bold mb-0 tracking-wider text-body" id="realtime-jam">--:--:--</h2>
                    <p class="text-primary fw-bold mb-0 mt-1" id="realtime-tanggal">...</p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 flex-grow-1 bg-white">
                <div class="card-body p-5 d-flex flex-column justify-content-center align-items-center text-center">
                    <div class="mb-4 p-4 bg-success bg-opacity-10 rounded-circle">
                        <i class="bi bi-file-earmark-spreadsheet-fill text-success display-1"></i>
                    </div>

                    <h4 class="fw-bold mb-2 text-body text-dark-emphasis">Laporan Kehadiran</h4>
                    <p class="text-muted mb-4 small">
                        Unduh rekapitulasi data absensi karyawan untuk keperluan evaluasi dan penggajian.
                    </p>

                    <button class="btn btn-success w-100 py-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="bi bi-download me-2"></i> Download Laporan (CSV)
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- MODAL EXPORT (Sama seperti admin tapi action routenya beda) --}}
<div class="modal fade" id="exportModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Export Data Laporan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      {{-- Route diarahkan ke manajemen --}}
      <form action="{{ route('manajemen.laporan.export') }}" method="POST">
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

    /* === DARK MODE OVERRIDES === */
    .dark-mode .bg-success-soft { background-color: #052c1e !important; border: 1px solid #0f5132; }
    .dark-mode .text-success-dark { color: #75b798 !important; }

    .dark-mode .bg-primary-soft { background-color: #031633 !important; border: 1px solid #084298; }
    .dark-mode .text-primary-dark { color: #6ea8fe !important; }

    .dark-mode .bg-warning-soft { background-color: #332701 !important; border: 1px solid #664d03; }
    .dark-mode .text-warning-dark { color: #ffda6a !important; }

    .dark-mode .bg-danger-soft { background-color: #2c0b0e !important; border: 1px solid #842029; }
    .dark-mode .text-danger-dark { color: #ea868f !important; }

    .dark-mode .text-dark-emphasis { color: #e0e0e0 !important; }
    .dark-mode .text-body { color: #e0e0e0 !important; }
    
    .dark-mode .card { background-color: #1e1e1e !important; border: 1px solid #333; }
    
    /* FIX: Memaksa bg-white menjadi gelap di dark mode */
    .dark-mode .bg-white { background-color: #1e1e1e !important; color: #fff !important; }
    
    .dark-mode .card-header {
        background-color: #252525 !important;
        border-bottom-color: #444 !important;
        color: #fff !important;
    }
    
    .dark-mode .text-muted { color: #aaa !important; }

    /* Modal di Dark Mode */
    .dark-mode .modal-content { background-color: #1e1e1e; color: #fff; }
    .dark-mode .modal-footer.bg-light { background-color: #252525 !important; }
    .dark-mode .btn-close { filter: invert(1); }
    .dark-mode .form-control { background-color: #2b2b2b; border-color: #444; color: #fff; }
    .dark-mode .btn-light { background-color: #333; border-color: #444; color: #fff; }
    .dark-mode .btn-light:hover { background-color: #444; }
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
        setInterval(updateClock, 60000);
    });
</script>
@endpush