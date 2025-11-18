@extends('layouts.app')

@section('page-title', 'Riwayat Absensi')

@section('content')
    <div class="d-flex flex-column h-100">

        {{-- Card Summary Atas --}}
        <div class="row mb-4">
            {{-- ... (Kode Card Nama, Jam, Tanggal, Lokasi TETAP SAMA, tidak saya tulis ulang agar hemat tempat) ... --}}
             {{-- Silakan copy bagian card summary dari kode sebelumnya jika hilang --}}
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                <i class="bi bi-person-fill text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted small mb-1">Nama Karyawan</h6>
                                <h5 class="fw-bold mb-0">{{ $karyawan->nama ?? 'Nama Tidak Ditemukan' }}</h5>
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
                                <h5 class="fw-bold mb-0">Klinik Grand Warden</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 

        <div class="row flex-grow-1">
            <div class="col-lg-8 d-flex flex-column">

                {{-- Statistik Absensi --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <h6 class="text-muted small">Hadir (Total)</h6>
                                <h5 class="fw-bold">{{ $riwayatAbsensi->count() }}</h5>
                            </div>
                            <div class="col-4">
                                <h6 class="text-muted small">Izin</h6>
                                <h5 class="fw-bold">-</h5>
                            </div>
                            <div class="col-4">
                                <h6 class="text-muted small">Sakit</h6>
                                <h5 class="fw-bold">-</h5>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- List Riwayat Absensi Dinamis --}}
                <div class="card shadow-sm border-0 flex-grow-1">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3 fw-bold">Riwayat Kehadiran</h5>

                        @if($riwayatAbsensi->isEmpty())
                            <div class="alert alert-info text-center">Belum ada data absensi.</div>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($riwayatAbsensi as $absen)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <div>
                                            {{-- Tanggal --}}
                                            <strong>{{ \Carbon\Carbon::parse($absen->waktu_unggah)->translatedFormat('d F Y') }}</strong>
                                            <br>
                                            {{-- Waktu & Tipe (Masuk/Pulang) --}}
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($absen->waktu_unggah)->format('H:i:s') }}
                                                ({{ ucfirst($absen->type) }})
                                            </small>
                                        </div>

                                        {{-- LOGIKA STATUS VALIDASI --}}
                                        @php
                                            // Ambil status final dari relasi validation
                                            // Jika belum ada validasi, default ke 'pending'
                                            $status = $absen->validation ? $absen->validation->status_validasi_final : 'Pending';
                                        @endphp

                                        @if($status == 'Valid')
                                            <span class="badge bg-success">Diterima</span>
                                        @elseif($status == 'Invalid')
                                            <span class="badge bg-danger">Ditolak</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar Kanan --}}
            <div class="col-lg-4 d-flex">
                <div class="card shadow-sm border-0 w-100">
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                         <h5 class="fw-bold mb-3">Info</h5>
                         <p class="text-muted">Grafik kehadiran akan muncul setelah data tersedia lebih banyak.</p>
                         <i class="bi bi-bar-chart-line display-1 text-light"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('styles')
<style>
    .card { border-radius: 12px; }
    .list-group-item { border-bottom: 1px solid #f0f0f0 !important; }
    .list-group-item:last-child { border-bottom: none !important; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
                const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                const dateStr = now.toLocaleDateString('id-ID', options).replace('.', ',');
                dateEl.textContent = dateStr;
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);
    });
</script>
@endpush