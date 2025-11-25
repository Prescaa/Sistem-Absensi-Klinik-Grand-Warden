@extends('layouts.admin_app')

@section('page-title', 'Dashboard Admin')

@section('content')
<div class="d-flex flex-column h-100">

    <div class="row flex-grow-1">

        {{-- KOLOM KIRI --}}
        <div class="col-lg-8 d-flex flex-column">

            {{-- STATISTIK CARDS --}}
            <div class="row g-3 mb-4">
                {{-- Card Karyawan --}}
                <div class="col-md-3">
                    <div class="card card-stat bg-success-soft border-0 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-success-dark small fw-bold mb-1">Karyawan</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $totalEmployees }}</h3>
                                </div>
                                <div class="icon-box bg-success text-white rounded-circle">
                                    <i class="bi bi-people-fill fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Hadir --}}
                <div class="col-md-3">
                    <div class="card card-stat bg-primary-soft border-0 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-primary-dark small fw-bold mb-1">Hadir</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $presentCount }}</h3>
                                </div>
                                <div class="icon-box bg-primary text-white rounded-circle">
                                    <i class="bi bi-person-check-fill fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Izin --}}
                <div class="col-md-3">
                    <div class="card card-stat bg-warning-soft border-0 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-warning-dark small fw-bold mb-1">Izin</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $izinCount }}</h3>
                                </div>
                                <div class="icon-box bg-warning text-white rounded-circle">
                                    <i class="bi bi-calendar-check-fill fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Sakit --}}
                <div class="col-md-3">
                    <div class="card card-stat bg-danger-soft border-0 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-danger-dark small fw-bold mb-1">Sakit</h6>
                                    <h3 class="fw-bold mb-0 text-dark-emphasis">{{ $sakitCount }}</h3>
                                </div>
                                <div class="icon-box bg-danger text-white rounded-circle">
                                    <i class="bi bi-heart-pulse-fill fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{--
                === FITUR BARU: ANALISIS TREN KEHADIRAN ===
                Menampilkan grafik batang sederhana menggunakan CSS
            --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                    <h5 class="card-title fw-bold mb-0 text-dark-emphasis">
                        <i class="bi bi-graph-up-arrow me-2 text-primary"></i>Analisis Tren Kehadiran
                    </h5>
                    <small class="text-muted">7 Hari Terakhir</small>
                </div>
                <div class="card-body pt-0 pb-4">
                    <div class="d-flex align-items-end justify-content-between px-2" style="height: 200px;">
                        @if(isset($chartData) && count($chartData) > 0)
                            @foreach($chartData as $index => $data)
                                <div class="text-center w-100 d-flex flex-column justify-content-end align-items-center h-100 mx-1">
                                    {{-- Tooltip angka saat hover --}}
                                    <div class="mb-1 fw-bold text-primary small">{{ $data }}</div>

                                    {{-- Batang Grafik --}}
                                    @php
                                        // Hitung persentase tinggi berdasarkan total karyawan (max 100%)
                                        $heightPercent = $totalEmployees > 0 ? ($data / $totalEmployees) * 100 : 0;
                                        // Warna batang: Biru tua jika > 80%, Kuning jika > 50%, Merah jika rendah
                                        $colorClass = $heightPercent >= 80 ? 'bg-primary' : ($heightPercent >= 50 ? 'bg-warning' : 'bg-danger');
                                    @endphp

                                    {{--
                                        ✅ PERBAIKAN ERROR VS CODE:
                                        Menggunakan CSS Variable (--bar-h) agar VS Code tidak bingung membaca sintaks Blade {{ }}
                                        di dalam properti height.
                                    --}}
                                    <div class="rounded-top {{ $colorClass }} w-100"
                                         style="--bar-h: {{ $heightPercent }}%; height: var(--bar-h); min-height: 4px; opacity: 0.8; transition: height 0.5s ease;"
                                         title="{{ $data }} Karyawan Hadir">
                                    </div>

                                    {{-- Label Tanggal --}}
                                    <small class="d-block mt-2 text-muted fw-bold" style="font-size: 0.7rem;">{{ $chartLabels[$index] }}</small>
                                </div>
                            @endforeach
                        @else
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                Data kehadiran belum cukup untuk ditampilkan.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 flex-grow-1">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        {{-- Judul Disesuaikan --}}
                        <h5 class="card-title fw-bold mb-0 me-2 text-dark-emphasis">Riwayat Aktivitas Terbaru</h5>
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse($recentActivities as $act)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-bottom bg-transparent">
                                <div class="d-flex align-items-center">

                                    {{-- LOGIKA TAMPILAN: ABSENSI vs IZIN --}}
                                    @if(class_basename($act) == 'Attendance')
                                        {{-- === TAMPILAN ABSENSI === --}}

                                        {{-- Avatar --}}
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="bi bi-camera-fill"></i>
                                        </div>

                                        <div>
                                            <span class="fw-bold text-dark-emphasis d-block">{{ $act->employee->nama ?? 'Karyawan' }}</span>
                                            <div class="text-muted small">
                                                <span class="text-primary fw-bold">Absen {{ ucfirst($act->type) }}</span>
                                                &bull; {{ $act->waktu_unggah->format('d M, H:i') }}
                                            </div>
                                        </div>

                                    @else
                                        {{-- === TAMPILAN IZIN === --}}

                                        {{-- Icon Izin --}}
                                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="bi bi-file-earmark-text-fill"></i>
                                        </div>

                                        <div>
                                            <span class="fw-bold text-dark-emphasis d-block">{{ $act->employee->nama ?? 'Karyawan' }}</span>
                                            <div class="text-muted small">
                                                <span class="text-warning fw-bold">Pengajuan {{ ucfirst($act->tipe_izin) }}</span>
                                                &bull; {{ $act->created_at->format('d M, H:i') }}
                                            </div>
                                        </div>
                                    @endif

                                </div>

                                {{-- BADGE STATUS --}}
                                <div>
                                    @if(class_basename($act) == 'Attendance')
                                        {{-- Status Absensi --}}
                                        @if($act->validation)
                                            @if($act->validation->status_validasi_final == 'Valid')
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">Valid</span>
                                            @else
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2">Invalid</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2">Pending</span>
                                        @endif
                                    @else
                                        {{-- Status Izin --}}
                                        @if($act->status == 'disetujui')
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">Disetujui</span>
                                        @elseif($act->status == 'ditolak')
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2">Ditolak</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2">Pending</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 mb-2 d-block opacity-25"></i>
                                <small>Belum ada aktivitas terbaru.</small>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN --}}
        <div class="col-lg-4 d-flex flex-column">

            {{-- WAKTU & TANGGAL --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center p-4">
                    <h6 class="text-muted mb-1 small text-uppercase fw-bold">Waktu Saat Ini</h6>
                    <h2 class="fw-bold mb-0 tracking-wider text-dark-emphasis" id="realtime-jam">--:--:--</h2>
                    <p class="text-primary fw-bold mb-0 mt-1" id="realtime-tanggal">...</p>
                </div>
            </div>

            {{-- DOWNLOAD LAPORAN --}}
            <div class="card shadow-sm border-0 mb-4 flex-grow-1">
                <div class="card-body p-4 d-flex flex-column justify-content-center align-items-center text-center">
                    <div class="mb-4 p-3 bg-success bg-opacity-10 rounded-circle">
                        <i class="bi bi-file-earmark-spreadsheet-fill text-success display-4"></i>
                    </div>

                    <h5 class="fw-bold mb-2 text-dark-emphasis">Laporan Absensi</h5>
                    <p class="text-muted mb-4 small">
                        Unduh rekapitulasi data kehadiran karyawan (Excel/CSV) untuk keperluan arsip bulanan.
                    </p>

                    <button class="btn btn-success w-100 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="bi bi-download me-2"></i> Download Laporan
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- MODAL EXPORT --}}
<div class="modal fade" id="exportModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Export Data Laporan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('admin.laporan.export') }}" method="POST">
          @csrf
          <div class="modal-body pt-4">
              <div class="mb-3">
                  <label class="form-label fw-bold small text-muted">Dari Tanggal</label>
                  <input type="date" name="start_date" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label fw-bold small text-muted">Sampai Tanggal</label>
                  <input type="date" name="end_date" class="form-control" required>
              </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4 fw-bold">Download CSV</button>
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
        width: 40px; height: 40px;
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
