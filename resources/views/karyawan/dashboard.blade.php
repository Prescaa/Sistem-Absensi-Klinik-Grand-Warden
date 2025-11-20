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
                
                <div class="card-body p-4 position-relative d-flex flex-column justify-content-center">
                    <h3 class="fw-bold mb-1">Halo, {{ Auth::user()->employee->nama }}!</h3>
                    <p class="mb-0 opacity-75">{{ Auth::user()->employee->posisi ?? 'Karyawan' }}</p>
                    
                    <hr class="border-white opacity-25 my-3">
                    
                    <div class="row">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <div class="bg-white bg-opacity-25 rounded p-1 me-2">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <small class="d-block opacity-75" style="font-size: 0.7rem;">Departemen</small>
                                    <span class="fw-bold">{{ Auth::user()->employee->departemen ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-white bg-opacity-25 rounded p-1 me-2">
                                    <i class="bi bi-card-heading"></i>
                                </div>
                                <div>
                                    <small class="d-block opacity-75" style="font-size: 0.7rem;">NIP</small>
                                    <span class="fw-bold">{{ Auth::user()->employee->nip ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-3 mt-md-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                    <h6 class="text-muted mb-2">Waktu Saat Ini</h6>
                    <h2 class="fw-bold mb-0 display-4" id="realtime-jam">--:--</h2>
                    <p class="text-primary fw-bold mb-0 mt-1" id="realtime-tanggal">...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row flex-grow-1">
        
        <div class="col-lg-7 mb-3 mb-lg-0 d-flex">
            <div class="card shadow-sm border-0 w-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark">Status Kehadiran Hari Ini</h5>
                </div>
                <div class="card-body p-5 d-flex flex-column justify-content-center align-items-center">
                    
                    {{-- LOGIKA TAMPILAN STATUS --}}
                    @if(isset($todayLeave) && $todayLeave)
                        <div class="text-center">
                            <div class="mb-4"><i class="bi bi-calendar-x-fill text-info" style="font-size: 6rem;"></i></div>
                            <h2 class="fw-bold text-info">Sedang {{ ucfirst($todayLeave->tipe_izin) }}</h2>
                            <p class="text-muted fs-5">
                                Anda tercatat sedang {{ $todayLeave->tipe_izin }}.<br>
                                <small>Ket: {{ $todayLeave->deskripsi }}</small>
                            </p>
                        </div>

                    @elseif($absensiMasuk && $absensiPulang)
                        <div class="text-center">
                            <div class="mb-4"><i class="bi bi-check-circle-fill text-success" style="font-size: 6rem;"></i></div>
                            <h2 class="fw-bold text-success">Absensi Selesai</h2>
                            <p class="text-muted fs-5">Terima kasih atas kerja keras Anda hari ini.</p>
                            
                            <div class="d-flex gap-4 mt-4 justify-content-center">
                                <div class="bg-light rounded p-3 px-4 border text-center">
                                    <small class="d-block text-muted mb-1">Masuk</small>
                                    <strong class="fs-4 text-dark">{{ \Carbon\Carbon::parse($absensiMasuk->waktu_unggah)->format('H:i') }}</strong>
                                </div>
                                <div class="bg-light rounded p-3 px-4 border text-center">
                                    <small class="d-block text-muted mb-1">Pulang</small>
                                    <strong class="fs-4 text-dark">{{ \Carbon\Carbon::parse($absensiPulang->waktu_unggah)->format('H:i') }}</strong>
                                </div>
                            </div>
                        </div>

                    @elseif($absensiMasuk)
                        <div class="text-center">
                            <div class="mb-4"><div class="spinner-grow text-primary" style="width: 5rem; height: 5rem;" role="status"></div></div>
                            <h2 class="fw-bold text-primary">Sedang Bekerja</h2>
                            <p class="text-muted fs-5">
                                Masuk pukul <strong>{{ \Carbon\Carbon::parse($absensiMasuk->waktu_unggah)->format('H:i') }}</strong>.
                                <br>Semangat bekerja!
                            </p>
                        </div>

                    @else
                        <div class="text-center">
                            <div class="mb-4"><i class="bi bi-alarm text-warning" style="font-size: 6rem;"></i></div>
                            <h2 class="fw-bold text-dark">Belum Absen</h2>
                            <p class="text-muted fs-5">Silakan melakukan absensi masuk di panel sebelah kanan.</p>
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
                <div class="card-body p-5 d-flex flex-column justify-content-center gap-4">
                    
                    @if(isset($todayLeave) && $todayLeave)
                        {{-- JIKA SEDANG IZIN: Matikan Tombol --}}
                        <div class="alert alert-info text-center border-0 bg-info bg-opacity-10">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Absensi dinonaktifkan karena Anda sedang dalam masa <strong>{{ ucfirst($todayLeave->tipe_izin) }}</strong>.
                        </div>
                        <button class="btn btn-light btn-lg py-4 shadow-sm disabled border">
                            Absensi Dinonaktifkan
                        </button>

                    @else
                        {{-- NORMAL FLOW --}}
                        <a href="{{ route('karyawan.absensi.unggah', ['type' => 'masuk']) }}" 
                           class="btn btn-lg py-4 d-flex align-items-center justify-content-between shadow-sm {{ $absensiMasuk ? 'btn-light text-muted disabled border' : 'btn-primary' }}">
                            <div class="d-flex align-items-center">
                                <div class="bg-white bg-opacity-25 rounded-circle p-3 me-4"><i class="bi bi-box-arrow-in-right fs-3"></i></div>
                                <div class="text-start">
                                    <div class="fw-bold fs-5">Absen Masuk</div>
                                    <small class="opacity-75">Mulai jam kerja Anda</small>
                                </div>
                            </div>
                            @if($absensiMasuk) <i class="bi bi-check-circle-fill text-success fs-3"></i> @else <i class="bi bi-chevron-right fs-4"></i> @endif
                        </a>

                        <a href="{{ route('karyawan.absensi.unggah', ['type' => 'pulang']) }}" 
                           class="btn btn-lg py-4 d-flex align-items-center justify-content-between shadow-sm {{ !$absensiMasuk || $absensiPulang ? 'btn-light text-muted disabled border' : 'btn-danger' }}">
                            <div class="d-flex align-items-center">
                                <div class="bg-white bg-opacity-25 rounded-circle p-3 me-4"><i class="bi bi-box-arrow-right fs-3"></i></div>
                                <div class="text-start">
                                    <div class="fw-bold fs-5">Absen Pulang</div>
                                    <small class="opacity-75">Akhiri jam kerja Anda</small>
                                </div>
                            </div>
                             @if($absensiPulang) <i class="bi bi-check-circle-fill text-success fs-3"></i> @elseif(!$absensiMasuk) <i class="bi bi-lock-fill fs-4"></i> @else <i class="bi bi-chevron-right fs-4"></i> @endif
                        </a>
                    @endif
                    
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .card { border-radius: 15px; }
    .btn-lg { border-radius: 15px; transition: transform 0.2s; }
    .btn-lg:hover:not(.disabled) { transform: translateY(-3px); }
    .user-card {
        background: linear-gradient(45deg, #0d6efd, #0a58ca);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil waktu server dari PHP (Blade) saat halaman diload
        // Pastikan formatnya aman untuk parsing JS (ISO string atau timestamp ms)
        let serverTime = new Date("{{ now()->format('Y-m-d H:i:s') }}").getTime();

        function updateDateTime() {
            // Tambah 1 detik setiap interval
            serverTime += 1000; 
            
            const now = new Date(serverTime);
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
        
        // Jalankan sekali di awal biar gak nunggu 1 detik
        updateDateTime(); 
        setInterval(updateDateTime, 1000);
    });
</script>
@endpush