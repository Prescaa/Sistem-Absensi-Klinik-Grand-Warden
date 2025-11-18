{{-- resources/views/karyawan/unggah.blade.php --}}

@extends('layouts.app')

@section('page-title', 'Unggah Foto Absensi')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="card-title text-center mb-4">Unggah Foto Absensi</h4>

                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ session('error') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- LOGIKA PENGECEKAN STATUS --}}
                    @php
                        // Cek apakah hari ini sedang Izin/Sakit (Variabel dari controller)
                        $sedangIzin = isset($todayLeave) && $todayLeave;

                        // Cek apakah sudah selesai absen hari ini (Masuk & Pulang ada)
                        $selesaiAbsen = $absensiMasuk && $absensiPulang;

                        // Tentukan apakah form harus dinonaktifkan
                        $isDisabled = $sedangIzin || $selesaiAbsen;
                    @endphp

                    @if($sedangIzin)
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info-emphasis mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Anda sedang dalam masa {{ ucfirst($todayLeave->tipe_izin) }}.</strong><br>
                            Tidak perlu melakukan upload absensi hari ini.
                        </div>
                    @elseif($selesaiAbsen)
                        <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success-emphasis mb-4">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Absensi Hari Ini Selesai.</strong><br>
                            Anda sudah melakukan absen masuk dan pulang.
                        </div>
                    @endif

                    <form action="{{ route('karyawan.absensi.storeFoto') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="foto_absensi" class="form-label">Pilih Foto (Wajib ada GPS)</label>

                            {{-- IMPLEMENTASI: Tombol File Disable jika $isDisabled bernilai true --}}
                            <input class="form-control" type="file" id="foto_absensi" name="foto_absensi"
                                   accept="image/jpeg,image/png"
                                   {{ $isDisabled ? 'disabled' : 'required' }}>

                            <div class="form-text">
                                Pastikan foto diambil langsung dengan kamera dan layanan lokasi (GPS) di HP Anda sudah aktif.
                            </div>
                        </div>

                        <div class="mb-3 text-center">
                            <img id="imagePreview" src="https://via.placeholder.com/400x300.png?text=Preview+Foto+Anda" alt="Image Preview" class="img-fluid rounded" style="max-height: 300px; border: 1px solid #ddd;">
                        </div>

                        {{-- BLOK TOMBOL AKSI --}}
                        <div class="row g-2">
                            {{-- Jika sedang izin, semua tombol dimatikan --}}
                            @if($sedangIzin)
                                <div class="col-12">
                                    <button type="button" class="btn btn-secondary w-100" disabled>
                                        <i class="bi bi-slash-circle me-2"></i> Absensi Dinonaktifkan ({{ ucfirst($todayLeave->tipe_izin) }})
                                    </button>
                                </div>
                            @else
                                {{-- Logika Normal Absensi --}}
                                <div class="col-6">
                                    @if(is_null($absensiMasuk))
                                        {{-- 1. Belum absen masuk hari ini --}}
                                        <button type="submit" name="type" value="masuk"
                                                class="btn btn-primary btn-lg d-block w-100">
                                            <i class="bi bi-box-arrow-in-right me-2"></i> Absen Masuk
                                        </button>
                                    @else
                                        {{-- 2. Sudah absen masuk --}}
                                        <button type="button" class="btn btn-success btn-lg d-block w-100" disabled>
                                            <i class="bi bi-check-circle-fill me-2"></i> Sudah Masuk
                                        </button>
                                    @endif
                                </div>

                                <div class="col-6">
                                    @if(is_null($absensiMasuk))
                                        {{-- 1. Belum absen masuk, tidak bisa pulang --}}
                                        <button type="button" class="btn btn-secondary btn-lg d-block w-100" disabled
                                                title="Harap absen masuk terlebih dahulu">
                                            <i class="bi bi-box-arrow-right me-2"></i> Absen Pulang
                                        </button>
                                    @elseif(is_null($absensiPulang))
                                        {{-- 2. Sudah masuk, belum pulang --}}
                                        <button type="submit" name="type" value="pulang"
                                                class="btn btn-outline-secondary btn-lg d-block w-100">
                                            <i class="bi bi-box-arrow-right me-2"></i> Absen Pulang
                                        </button>
                                    @else
                                        {{-- 3. Sudah pulang --}}
                                        <button type="button" class="btn btn-dark btn-lg d-block w-100" disabled>
                                            <i class="bi bi-check-circle-fill me-2"></i> Sudah Pulang
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('foto_absensi');
        const imagePreview = document.getElementById('imagePreview');

        if(fileInput) {
            fileInput.addEventListener('change', function(event) {
                const [file] = event.target.files;
                if (file) {
                    imagePreview.src = URL.createObjectURL(file);
                    imagePreview.onload = () => {
                        URL.revokeObjectURL(imagePreview.src);
                    }
                }
            });
        }
    });
</script>
@endpush
