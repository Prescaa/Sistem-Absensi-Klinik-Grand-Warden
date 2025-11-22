@extends('layouts.app')

@section('page-title', 'Riwayat Absensi')

@section('content')
    <div class="d-flex flex-column h-100">

        {{-- Card Summary Atas (Header Statistik) --}}
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
                                <h5 class="fw-bold mb-0 text-dark-emphasis">{{ $karyawan->nama ?? 'Nama Tidak Ditemukan' }}</h5>
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
                                <h5 class="fw-bold mb-0 text-dark-emphasis" id="current-time">--:--</h5>
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
                                <h5 class="fw-bold mb-0 text-dark-emphasis" id="current-date">...</h5>
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
                                <h5 class="fw-bold mb-0 text-dark-emphasis">Klinik Grand Warden</h5>
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
                            {{-- 1. Hadir --}}
                            <div class="col-6 col-md-3 border-end">
                                <h6 class="text-muted small mb-1">Hadir (Total)</h6>
                                <h5 class="fw-bold text-dark-emphasis">{{ $riwayatAbsensi->count() }}</h5>
                            </div>
                            
                            {{-- 2. Cuti --}}
                            <div class="col-6 col-md-3 border-end">
                                <h6 class="text-muted small mb-1">Cuti</h6>
                                <h5 class="fw-bold text-dark-emphasis">{{ $cutiCount ?? 0 }}</h5> 
                            </div>
                            
                            {{-- 3. Izin --}}
                            <div class="col-6 col-md-3 border-end">
                                <h6 class="text-muted small mb-1">Izin</h6>
                                <h5 class="fw-bold text-warning">{{ $izinCount ?? 0 }}</h5>
                            </div>
                            
                            {{-- 4. Sakit --}}
                            <div class="col-6 col-md-3">
                                <h6 class="text-muted small mb-1">Sakit</h6>
                                <h5 class="fw-bold text-danger">{{ $sakitCount ?? 0 }}</h5>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tab Navigation untuk Riwayat Absensi dan Izin --}}
                <div class="card shadow-sm border-0 flex-grow-1">
                    <div class="card-body p-4">
                        <ul class="nav nav-tabs mb-3" id="riwayatTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                {{-- Tab ID: #absensi --}}
                                <button class="nav-link active fw-bold" id="absensi-tab" data-bs-toggle="tab" data-bs-target="#absensi" type="button" role="tab">
                                    <i class="bi bi-clock-history me-2"></i>Riwayat Kehadiran
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                {{-- Tab ID: #izin --}}
                                <button class="nav-link fw-bold" id="izin-tab" data-bs-toggle="tab" data-bs-target="#izin" type="button" role="tab">
                                    <i class="bi bi-calendar-check me-2"></i>Riwayat Izin, Sakit & Cuti
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="riwayatTabContent">
                            {{-- TAB 1: Riwayat Absensi --}}
                            <div class="tab-pane fade show active" id="absensi" role="tabpanel">
                                @if($riwayatAbsensi->isEmpty())
                                    <div class="alert alert-info text-center">Belum ada data absensi.</div>
                                @else
                                    <ul class="list-group list-group-flush">
                                        @foreach($riwayatAbsensi as $absen)
                                            <li class="list-group-item px-0 py-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div>
                                                        {{-- Tanggal --}}
                                                        <strong class="text-dark-emphasis">{{ \Carbon\Carbon::parse($absen->waktu_unggah)->translatedFormat('d F Y') }}</strong>
                                                        <br>
                                                        {{-- Waktu & Tipe (Masuk/Pulang) --}}
                                                        <small class="text-muted">
                                                            {{ \Carbon\Carbon::parse($absen->waktu_unggah)->format('H:i:s') }}
                                                            @if($absen->type == 'masuk')
                                                                <span class="text-success fw-bold">(Masuk)</span>
                                                            @else
                                                                <span class="text-primary fw-bold">(Pulang)</span>
                                                            @endif
                                                        </small>
                                                    </div>

                                                    {{-- STATUS VALIDASI --}}
                                                    <div>
                                                        @if($absen->validation)
                                                            @php
                                                                $status = strtolower($absen->validation->status_validasi_final ?? $absen->validation->status_validasi ?? 'pending');
                                                            @endphp

                                                            @if(in_array($status, ['approved', 'valid', 'diterima', 'setuju']))
                                                                <span class="badge bg-success">Diterima</span>
                                                            @elseif(in_array($status, ['rejected', 'invalid', 'ditolak', 'gagal']))
                                                                <span class="badge bg-danger">Ditolak</span>
                                                            @else
                                                                <span class="badge bg-warning text-dark">Diproses</span>
                                                            @endif
                                                        @else
                                                            <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- CATATAN ADMIN --}}
                                                @if($absen->validation && !empty($absen->validation->catatan_admin))
                                                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 p-2 rounded small mb-0">
                                                        <div class="d-flex">
                                                            <i class="bi bi-exclamation-circle-fill text-warning me-2 mt-1"></i>
                                                            <div>
                                                                <strong>Catatan Admin:</strong><br>
                                                                <span class="text-dark-emphasis">{{ $absen->validation->catatan_admin }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            {{-- TAB 2: Riwayat Izin, Sakit & Cuti --}}
                            <div class="tab-pane fade" id="izin" role="tabpanel">
                                @if($riwayatIzin->isEmpty())
                                    <div class="alert alert-info text-center">Belum ada riwayat izin, sakit, atau cuti.</div>
                                @else
                                    <ul class="list-group list-group-flush">
                                        @foreach($riwayatIzin as $izin)
                                            <li class="list-group-item px-0 py-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="flex-grow-1">
                                                        {{-- Tanggal --}}
                                                        <strong class="text-dark-emphasis">
                                                            {{ \Carbon\Carbon::parse($izin->tanggal_mulai)->translatedFormat('d F Y') }}
                                                            @if($izin->tanggal_mulai != $izin->tanggal_selesai)
                                                                - {{ \Carbon\Carbon::parse($izin->tanggal_selesai)->translatedFormat('d F Y') }}
                                                            @endif
                                                        </strong>
                                                        <br>
                                                        {{-- Tipe Izin --}}
                                                        <small class="text-muted">
                                                            @if($izin->tipe_izin == 'sakit')
                                                                <span class="text-danger fw-bold">(Sakit)</span>
                                                            @elseif($izin->tipe_izin == 'cuti')
                                                                <span class="text-info fw-bold">(Cuti)</span>
                                                            @else
                                                                <span class="text-warning fw-bold">(Izin)</span>
                                                            @endif
                                                            - {{ $izin->deskripsi }}
                                                        </small>
                                                    </div>

                                                    {{-- STATUS IZIN --}}
                                                    <div class="text-end">
                                                        @if($izin->status == 'pending')
                                                            <span class="badge bg-secondary">Menunggu</span>
                                                        @elseif($izin->status == 'disetujui')
                                                            <span class="badge bg-success">Disetujui</span>
                                                        @else
                                                            <span class="badge bg-danger">Ditolak</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- CATATAN ADMIN --}}
                                                @if(!empty($izin->catatan_admin))
                                                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 p-2 rounded small mb-0">
                                                        <div class="d-flex">
                                                            <i class="bi bi-exclamation-circle-fill text-warning me-2 mt-1"></i>
                                                            <div>
                                                                <strong>Catatan Admin:</strong><br>
                                                                <span class="text-dark-emphasis">{{ $izin->catatan_admin }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- BUKTI DOKUMEN --}}
                                                @if($izin->file_bukti)
                                                    <div class="mt-2">
                                                        <a href="{{ asset($izin->file_bukti) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-paperclip me-1"></i>Lihat Bukti Dokumen
                                                        </a>
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar Kanan --}}
            <div class="col-lg-4 d-flex">
                <div class="card shadow-sm border-0 w-100">
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                         <h5 class="fw-bold mb-3 text-dark-emphasis">Info</h5>
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
    .nav-tabs .nav-link.active { 
        background-color: #fff; 
        border-color: #dee2e6 #dee2e6 #fff;
        color: #0d6efd;
        font-weight: bold;
    }

    /* Dark Mode Support untuk Riwayat */
    .dark-mode .text-dark-emphasis { 
        color: #e0e0e0 !important; 
    }
    .dark-mode .list-group-item { 
        background-color: #1e1e1e !important;
        border-bottom-color: #444 !important;
        color: #e0e0e0;
    }
    .dark-mode .nav-tabs .nav-link.active { 
        background-color: #1e1e1e !important; 
        border-color: #444 #444 #1e1e1e !important;
        color: #6ea8fe !important;
    }
    .dark-mode .nav-tabs .nav-link {
        color: #adb5bd !important;
    }
    .dark-mode .nav-tabs {
        border-bottom-color: #444 !important;
    }
    .dark-mode .alert-info {
        background-color: rgba(13, 202, 240, 0.1) !important;
        border-color: #0dcaf0 !important;
        color: #6edff6 !important;
    }
    .dark-mode .alert-warning {
        background-color: rgba(255, 193, 7, 0.1) !important;
        border-color: #ffc107 !important;
        color: #ffda6a !important;
    }
    .dark-mode .border-end {
        border-right-color: #444 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Skrip Jam Realtime
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

        // 2. ✅ PERBAIKAN: Fungsi Aktivasi Tab
        // Fungsi ini akan dijalankan saat load pertama kali DAN saat hash URL berubah
        function activateTabFromHash() {
            const hash = window.location.hash; 
            if (hash) {
                // Cari tombol tab yang targetnya sesuai dengan hash (#absensi atau #izin)
                const triggerEl = document.querySelector(`button[data-bs-target="${hash}"]`);
                if (triggerEl) {
                    // Gunakan click() sebagai metode paling robust 
                    // (bekerja meskipun objek Bootstrap JS belum ter-init secara global)
                    triggerEl.click();
                }
            }
        }

        // Jalankan saat halaman dimuat
        activateTabFromHash();

        // Jalankan saat hash berubah (misal: user klik notif padahal sedang di halaman riwayat)
        window.addEventListener('hashchange', activateTabFromHash);
    });
</script>
@endpush