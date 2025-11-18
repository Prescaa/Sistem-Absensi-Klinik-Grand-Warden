{{-- resources/views/karyawan/unggah.blade.php --}}

@extends('layouts.app') {{-- Menggunakan layout karyawan --}}

{{-- Judul halaman akan dinamis, contoh: "Unggah Foto Masuk" --}}
@section('page-title', 'Unggah Foto ' . ucfirst($type ?? 'Absensi'))

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="card-title text-center mb-4">Unggah Foto Absensi {{ ucfirst($type ?? '') }}</h4>

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

                    <form action="{{ route('karyawan.absensi.storeFoto') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" name="type" value="{{ $type ?? '' }}">

                        <div class="mb-3">
                            <label for="foto_absensi" class="form-label">Pilih Foto (Wajib ada GPS)</label>
                            <input class="form-control" type="file" id="foto_absensi" name="foto_absensi" accept="image/jpeg,image/png" required>
                            <div class="form-text">
                                Pastikan foto diambil langsung dengan kamera dan layanan lokasi (GPS) di HP Anda sudah aktif.
                            </div>
                        </div>

                        <div class="mb-3 text-center">
                            <img id="imagePreview" src="https://via.placeholder.com/400x300.png?text=Preview+Foto+Anda" alt="Image Preview" class="img-fluid rounded" style="max-height: 300px; border: 1px solid #ddd;">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-upload me-2"></i> Kirim dan Validasi Foto
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Script sederhana untuk menampilkan preview gambar yg dipilih --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('foto_absensi');
        const imagePreview = document.getElementById('imagePreview');

        fileInput.addEventListener('change', function(event) {
            const [file] = event.target.files;
            if (file) {
                // Membuat URL sementara untuk file yg dipilih
                imagePreview.src = URL.createObjectURL(file);
                // Hapus URL setelah gambar dimuat untuk bebaskan memori
                imagePreview.onload = () => {
                    URL.revokeObjectURL(imagePreview.src);
                }
            }
        });
    });
</script>
@endpush
