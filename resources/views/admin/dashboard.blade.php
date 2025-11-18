{{-- Menggunakan layout admin --}}
@extends('layouts.admin_app')

{{-- Mengatur judul halaman --}}
@section('page-title', 'Dashboard')

{{-- Konten utama halaman dashboard --}}
@section('content')
<div class="d-flex flex-column h-100">
    <div class="row flex-grow-1">

        {{-- KOLOM KIRI (Statistik & Menunggu Validasi) --}}
        <div class="col-lg-7 d-flex flex-column">
            <div class="row">
                {{-- KARYAWAN --}}
                <div class="col-md-6 mb-4">
                    <div class="card text-white bg-success shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-75 small">Jumlah Karyawan</h6>
                                    <h3 class="fw-bold mb-0">{{ $totalEmployees ?? 0 }}</h3>
                                </div>
                                <i class="bi bi-people-fill fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- HADIR --}}
                <div class="col-md-6 mb-4">
                    <div class="card text-white bg-primary shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-75 small">Hadir Hari Ini</h6>
                                    <h3 class="fw-bold mb-0">{{ $presentCount ?? 0 }}</h3>
                                </div>
                                <i class="bi bi-person-check-fill fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- IZIN --}}
                <div class="col-md-6 mb-4">
                    <div class="card text-dark bg-warning shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-dark-75 small">Izin</h6>
                                    <h3 class="fw-bold mb-0">{{ $izinCount ?? 0 }}</h3>
                                </div>
                                <i class="bi bi-calendar-check-fill fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SAKIT --}}
                <div class="col-md-6 mb-4">
                    <div class="card text-white bg-danger shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-75 small">Sakit</h6>
                                    <h3 class="fw-bold mb-0">{{ $sakitCount ?? 0 }}</h3>
                                </div>
                                <i class="bi bi-heart-pulse-fill fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD MENUNGGU VALIDASI --}}
            <div class="card shadow-sm border-0 mb-4 flex-grow-1">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <h5 class="card-title fw-bold mb-0 me-2">Menunggu Validasi Terbaru</h5>
                        @if(isset($recentActivities) && $recentActivities->count() > 0)
                            <span class="badge rounded-pill bg-danger">{{ $recentActivities->count() }}</span>
                        @endif
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse($recentActivities ?? [] as $item)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    {{-- Nama Karyawan --}}
                                    <strong>{{ $item->employee->nama ?? 'Karyawan' }}</strong>
                                    <div class="text-muted small">
                                        {{-- Tampilkan Waktu --}}
                                        <i class="bi bi-clock me-1"></i> {{ $item->waktu_unggah->format('d M Y, H:i') }}
                                        &mdash;
                                        {{-- Logika Tampilan Tipe --}}
                                        @if($item->type == 'masuk')
                                            <span class="text-success fw-bold">Absen Masuk</span>
                                        @elseif($item->type == 'pulang')
                                            <span class="text-warning fw-bold">Absen Pulang</span>
                                        @else
                                            {{ ucfirst($item->type) }}
                                        @endif
                                    </div>
                                </div>

                                {{-- Tombol Review --}}
                                <a href="{{ route('admin.validasi.show') }}" class="btn btn-sm btn-outline-primary">
                                    Review
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 d-block mb-3 text-secondary opacity-25"></i>
                                <p class="mb-0">Tidak ada absensi yang menunggu validasi saat ini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN (Jam & Ekspor Laporan) --}}
        <div class="col-lg-5 d-flex flex-column">
            {{-- Jam & Tanggal --}}
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <h6 class="text-muted">Jam Saat Ini</h6>
                            <h3 class="fw-bold display-6 mb-0" id="realtime-jam">--:--</h3>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <h6 class="text-muted">Tanggal Hari Ini</h6>
                            <h3 class="fw-bold" id="realtime-tanggal">...</h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD EKSPOR (Diperbesar mengisi sisa ruang) --}}
            <div class="card shadow-sm border-0 mb-4 flex-grow-1 bg-white">
                <div class="card-body p-5 d-flex flex-column justify-content-center align-items-center text-center">

                    <div class="mb-4 p-3 rounded-circle bg-light text-primary">
                        <i class="bi bi-file-earmark-spreadsheet-fill" style="font-size: 4rem;"></i>
                    </div>

                    <h4 class="card-title fw-bold mb-3">Laporan Absensi</h4>
                    <p class="text-muted mb-4 px-4">
                        Unduh rekapitulasi data absensi karyawan secara lengkap untuk keperluan arsip atau penggajian.
                    </p>

                    <a href="{{ route('admin.laporan.show') }}" class="btn btn-primary btn-lg w-75 py-3 shadow-sm">
                        <i class="bi bi-download me-2"></i> Buka Menu Laporan
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
    .card { border-radius: 12px; }
    .text-white-75 { color: rgba(255, 255, 255, 0.75) !important; }
    .text-dark-75 { color: rgba(0, 0, 0, 0.75) !important; }
    .card.bg-primary, .card.bg-success, .card.bg-info, .card.bg-warning, .card.bg-danger { border-radius: .75rem; }

    .list-group-item { border-bottom: 1px solid #f0f0f0 !important; }
    .list-group-item:last-child { border-bottom: none !important; }

    #realtime-tanggal { font-size: 1.2rem; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Script Jam & Tanggal Real-time
        function updateDateTime() {
            const jamElement = document.getElementById('realtime-jam');
            const tanggalElement = document.getElementById('realtime-tanggal');

            if (jamElement && tanggalElement) {
                const now = new Date();

                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');

                // Menambahkan detik agar lebih hidup
                jamElement.textContent = `${hours}:${minutes}:${seconds}`;

                const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                let tanggalStr = now.toLocaleDateString('id-ID', options);
                tanggalElement.textContent = tanggalStr;
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    });
</script>
@endpush
