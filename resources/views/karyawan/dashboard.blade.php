{{-- resources/views/karyawan/dashboard.blade.php --}}
@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')
<div class="container py-4">

    @if (session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="row flex-grow-1">
        <div class="col-lg-8 d-flex flex-column">

            {{-- LOGIKA BARU: Cek Status Izin Terlebih Dahulu --}}
            @if(isset($todayLeave) && $todayLeave)
                {{-- TAMPILAN JIKA SEDANG IZIN (Tombol Absen Hilang) --}}
                <div class="card shadow-sm border-0 mb-4 bg-info bg-opacity-10 border-info border-start border-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-calendar-check-fill text-white fs-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1 text-info-emphasis">Anda Sedang {{ ucfirst($todayLeave->tipe_izin) }}</h5>
                                <p class="mb-0 text-muted">
                                    Status kehadiran Anda telah tercatat otomatis berdasarkan pengajuan izin yang disetujui.<br>
                                    <small class="fw-bold">
                                        Periode: {{ $todayLeave->tanggal_mulai->format('d M') }} - {{ $todayLeave->tanggal_selesai->format('d M Y') }}
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                {{-- TAMPILAN NORMAL (JIKA TIDAK IZIN) --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3 fw-bold">Status Kehadiran Hari Ini</h5>
                        <div class="d-flex gap-3">

                            {{-- Tombol Masuk --}}
                            @if($absensiMasuk)
                                <div class="flex-fill p-3 bg-success bg-opacity-10 border border-success rounded text-center">
                                    <h6 class="text-success fw-bold mb-1">SUDAH MASUK</h6>
                                    <small>{{ $absensiMasuk->waktu_unggah->format('H:i') }}</small>
                                </div>
                            @else
                                <a href="{{ route('karyawan.absensi.unggah', ['type' => 'masuk']) }}" class="btn btn-primary flex-fill py-3 fw-bold">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Absen Masuk
                                </a>
                            @endif

                            {{-- Tombol Pulang --}}
                            @if($absensiPulang)
                                <div class="flex-fill p-3 bg-success bg-opacity-10 border border-success rounded text-center">
                                    <h6 class="text-success fw-bold mb-1">SUDAH PULANG</h6>
                                    <small>{{ $absensiPulang->waktu_unggah->format('H:i') }}</small>
                                </div>
                            @else
                                {{-- Tombol pulang hanya aktif jika sudah absen masuk --}}
                                <a href="{{ $absensiMasuk ? route('karyawan.absensi.unggah', ['type' => 'pulang']) : '#' }}"
                                   class="btn {{ $absensiMasuk ? 'btn-warning text-white' : 'btn-secondary disabled' }} flex-fill py-3 fw-bold">
                                    <i class="bi bi-box-arrow-left me-2"></i>Absen Pulang
                                </a>
                            @endif

                        </div>
                    </div>
                </div>
            @endif
</div>
@endsection
