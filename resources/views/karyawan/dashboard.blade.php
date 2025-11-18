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

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-check-fill me-2"></i> Absensi Hari Ini</h5>
                </div>
                <div class="card-body p-4">
                    {{-- Ini adalah logika baru berdasarkan Migrasi --}}

                    <h5 class="mb-3">Tanggal: {{ now()->format('l, d F Y') }}</h5>

                    {{-- Ini adalah blok kode asli yang dikembalikan sesuai permintaan Anda --}}
                    <div class="row g-2">
                        <div class="col-6">
                            @if(is_null($absensiMasuk))
                                {{-- 1. Belum absen masuk hari ini, tombol MASUK aktif --}}
                                <a href="{{ route('karyawan.absensi.unggah', ['type' => 'masuk']) }}"
                                   class="btn btn-primary btn-lg d-block">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Absen Masuk
                                </a>
                            @else
                                {{-- 2. Sudah absen masuk, tombol MASUK non-aktif --}}
                                <button class="btn btn-success btn-lg d-block" disabled>
                                    <i class="bi bi-check-circle-fill me-2"></i> Sudah Masuk
                                </button>
                            @endif
                        </div>

                        <div class="col-6">
                            @if(is_null($absensiMasuk))
                                {{-- 1. Belum absen masuk, tidak bisa pulang (non-aktif) --}}
                                <button class="btn btn-secondary btn-lg d-block" disabled
                                        title="Harap absen masuk terlebih dahulu">
                                    <i class="bi bi-box-arrow-right me-2"></i> Absen Pulang
                                </button>
                            @elseif(is_null($absensiPulang))
                                {{-- 2. Sudah masuk, belum pulang, tombol PULANG aktif --}}
                                <a href="{{ route('karyawan.absensi.unggah', ['type' => 'pulang']) }}"
                                   class="btn btn-outline-secondary btn-lg d-block">
                                    <i class="bi bi-box-arrow-right me-2"></i> Absen Pulang
                                </a>
                            @else
                                {{-- 3. Sudah pulang (non-aktif) --}}
                                <button class="btn btn-dark btn-lg d-block" disabled>
                                    <i class="bi bi-check-circle-fill me-2"></i> Sudah Pulang
                                </button>
                            @endif
                        </div>
                    </div>
                    {{-- Akhir dari blok kode yang dikembalikan --}}


                    {{-- Karena migrasi baru tidak punya 'status', kita tampilkan waktu saja --}}
                    @if($absensiMasuk || $absensiPulang)
                        <hr class="my-4">
                        <h6 class="text-muted mb-2">Waktu Terekam Hari Ini:</h6>
                        @if($absensiMasuk)
                            <p class="text-muted small mb-1">
                                <i class="bi bi-box-arrow-in-right text-success"></i>
                                Masuk: <strong>{{ $absensiMasuk->waktu_unggah->format('H:i:s') }}</strong>
                            </p>
                        @endif
                        @if($absensiPulang)
                            <p class="text-muted small mb-1">
                                <i class="bi bi-box-arrow-right text-dark"></i>
                                Pulang: <strong>{{ $absensiPulang->waktu_unggah->format('H:i:s') }}</strong>
                            </p>
                        @endif
                    @endif

                </div>
            </div>
        </div>

        </div>
</div>
@endsection
