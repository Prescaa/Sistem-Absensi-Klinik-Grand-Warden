@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<div class="d-flex flex-column h-100">
    
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100 bg-primary text-white overflow-hidden position-relative user-card">
                <div class="position-absolute top-0 end-0 opacity-10 me-n3 mt-n3">
                    <i class="bi bi-person-badge-fill" style="font-size: 10rem;"></i>
                </div>
                <div class="card-body p-4 position-relative">
                    <h3 class="fw-bold mb-1">Halo, {{ Auth::user()->employee->nama }}!</h3>
                    <p class="mb-0 opacity-75">{{ Auth::user()->employee->posisi ?? 'Karyawan' }} - {{ Auth::user()->employee->departemen ?? 'Umum' }}</p>
                    <hr class="border-white opacity-25 my-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-geo-alt-fill me-2"></i>
                        <span>Lokasi Kerja: <strong>Klinik Grand Warden</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-3 mt-md-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                    <h6 class="text-muted mb-2">Waktu Saat Ini</h6>
                    <h2 class="fw-bold mb-0" id="realtime-jam">--:--</h2>
                    <p class="text-primary fw-bold mb-0 mt-1" id="realtime-tanggal">...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-7 mb-3 mb-lg-0 d-flex">
            <div class="card shadow-sm border-0 w-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark">Status Kehadiran Hari Ini</h5>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-center align-items-center">
                    @if($absensiMasuk && $absensiPulang)
                        <div class="text-center">
                            <div class="mb-3"><i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i></div>
                            <h3 class="fw-bold text-success">Absensi Selesai</h3>
                            <p class="text-muted">Anda sudah melakukan absen masuk dan pulang.</p>
                            <div class="d-flex gap-3 mt-3 justify-content-center">
                                <div class="bg-light rounded p-2 px-3 border"><small>Masuk: <strong>{{ \Carbon\Carbon::parse($absensiMasuk->waktu_unggah)->format('H:i') }}</strong></small></div>
                                <div class="bg-light rounded p-2 px-3 border"><small>Pulang: <strong>{{ \Carbon\Carbon::parse($absensiPulang->waktu_unggah)->format('H:i') }}</strong></small></div>
                            </div>
                        </div>
                    @elseif($absensiMasuk)
                        <div class="text-center">
                            <div class="mb-3"><div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;"></div></div>
                            <h3 class="fw-bold text-primary">Sedang Bekerja</h3>
                            <p class="text-muted">Masuk pukul <strong>{{ \Carbon\Carbon::parse($absensiMasuk->waktu_unggah)->format('H:i') }}</strong>. Jangan lupa absen pulang!</p>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="mb-3"><i class="bi bi-alarm text-warning" style="font-size: 4rem;"></i></div>
                            <h3 class="fw-bold text-dark">Belum Absen</h3>
                            <p class="text-muted">Silakan klik tombol di samping untuk absen.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5 d-flex">
            <div class="card shadow-sm border-0 w-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark">Aksi Cepat</h5>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-center gap-3">
                    <a href="{{ route('karyawan.absensi.unggah', ['type' => 'masuk']) }}" 
                       class="btn btn-lg w-100 py-3 d-flex align-items-center justify-content-between shadow-sm {{ $absensiMasuk ? 'btn-light text-muted disabled border' : 'btn-primary' }}">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3"><i class="bi bi-box-arrow-in-right fs-4"></i></div>
                            <div class="text-start"><div class="fw-bold">Absen Masuk</div></div>
                        </div>
                        @if($absensiMasuk) <i class="bi bi-check-circle-fill text-success fs-4"></i> @else <i class="bi bi-chevron-right"></i> @endif
                    </a>

                    <a href="{{ route('karyawan.absensi.unggah', ['type' => 'pulang']) }}" 
                       class="btn btn-lg w-100 py-3 d-flex align-items-center justify-content-between shadow-sm {{ !$absensiMasuk || $absensiPulang ? 'btn-light text-muted disabled border' : 'btn-danger' }}">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3"><i class="bi bi-box-arrow-right fs-4"></i></div>
                            <div class="text-start"><div class="fw-bold">Absen Pulang</div></div>
                        </div>
                         @if($absensiPulang) <i class="bi bi-check-circle-fill text-success fs-4"></i> @elseif(!$absensiMasuk) <i class="bi bi-lock-fill"></i> @else <i class="bi bi-chevron-right"></i> @endif
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row flex-grow-1">
        
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Pengumuman</h5>
                    <span class="badge bg-danger rounded-pill">Baru</span>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item border-0 px-4 py-3">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 fw-bold text-dark">Maintenance Sistem</h6>
                            <small class="text-muted">1 Nov</small>
                        </div>
                        <p class="mb-1 small text-muted">Sistem akan offline pada hari Sabtu jam 23:00 untuk pemeliharaan rutin server.</p>
                    </div>
                    <div class="list-group-item border-0 px-4 py-3 bg-light">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 fw-bold text-dark">Libur Nasional</h6>
                            <small class="text-muted">25 Des</small>
                        </div>
                        <p class="mb-1 small text-muted">Kantor libur memperingati Hari Natal.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark">Agenda Mendatang</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-4 py-3 border-0 d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-2 text-center me-3" style="min-width: 60px;">
                                <span class="d-block fw-bold small">NOV</span>
                                <span class="d-block fw-bold fs-4">25</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Gajian Bulanan</h6>
                                <small class="text-muted">Periode Oktober - November</small>
                            </div>
                        </div>
                        <div class="list-group-item px-4 py-3 border-0 d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 text-warning rounded p-2 text-center me-3" style="min-width: 60px;">
                                <span class="d-block fw-bold small">DES</span>
                                <span class="d-block fw-bold fs-4">15</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Evaluasi Tahunan</h6>
                                <small class="text-muted">Persiapan laporan kinerja akhir tahun</small>
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
    .card { border-radius: 15px; }
    .btn-lg { border-radius: 12px; }
    .user-card {
        background: linear-gradient(45deg, #0d6efd, #0a58ca);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateDateTime() {
            const now = new Date();
            const timeEl = document.getElementById('realtime-jam');
            const dateEl = document.getElementById('realtime-tanggal');
            
            if (timeEl) {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                timeEl.textContent = `${hours}:${minutes}:${seconds}`;
            }
            
            if (dateEl) {
                const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                dateEl.textContent = now.toLocaleDateString('id-ID', options);
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    });
</script>
@endpush