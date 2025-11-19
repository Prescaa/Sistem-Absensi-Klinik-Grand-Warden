@extends('layouts.app')

@section('page-title', 'Pengaturan Profil')

@section('content')
    {{-- Pesan Sukses --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Pesan Error --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            {{-- Form Update Profil --}}
            <form action="{{ route('karyawan.profil.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row align-items-center">
                    
                    {{-- 
                       KOLOM KIRI: FOTO PROFIL 
                       - Foto diperbesar menjadi 280px
                       - Posisi ditengah vertikal
                    --}}
                    <div class="col-md-4 text-center d-flex flex-column justify-content-center align-items-center mb-5 mb-md-0" style="border-right: 1px solid #dee2e6; min-height: 400px;">
                        
                        <div class="position-relative mb-4">
                            {{-- Container Preview Foto --}}
                            <div id="foto-preview-container">
                                @if(isset($employee->foto_profil) && $employee->foto_profil)
                                    {{-- FOTO DIPERBESAR (280px) --}}
                                    <img src="{{ asset($employee->foto_profil) }}" class="rounded-circle shadow-lg object-fit-cover" alt="Foto Profil" style="width: 280px; height: 280px; border: 5px solid #fff;">
                                @else
                                    {{-- INISIAL DIPERBESAR (280px) --}}
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-lg" style="width: 280px; height: 280px; font-size: 7rem; border: 5px solid #fff;">
                                        {{ strtoupper(substr(Auth::user()->employee->nama ?? Auth::user()->username, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Input File Tersembunyi --}}
                            <input type="file" id="foto_input" name="foto_profil" class="d-none" accept="image/*">
                        </div>
                        
                        {{-- Tombol Aksi --}}
                        <div class="d-flex gap-2">
                            {{-- Tombol Ganti Foto --}}
                            <button type="button" class="btn btn-outline-primary px-4" onclick="document.getElementById('foto_input').click()">
                                <i class="bi bi-camera-fill me-2"></i> Ganti Foto
                            </button>

                            {{-- Tombol Hapus Foto (Hanya jika ada foto) --}}
                            @if(isset($employee->foto_profil) && $employee->foto_profil)
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteFotoModal">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- KOLOM KANAN: FORM DATA --}}
                    <div class="col-md-8 ps-md-5">
                        
                        <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Informasi Pribadi</h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Nama Lengkap</label>
                                <input type="text" class="form-control bg-light" value="{{ Auth::user()->employee->nama }}" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">NIP</label>
                                <input type="text" class="form-control bg-light" value="{{ Auth::user()->employee->nip ?? '-' }}" readonly disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Alamat Email</label>
                            <input type="email" class="form-control bg-light" value="{{ Auth::user()->email }}" readonly disabled>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Jabatan</label>
                                <input type="text" class="form-control bg-light" value="{{ Auth::user()->employee->posisi ?? '-' }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Departemen</label>
                                <input type="text" class="form-control bg-light" value="{{ Auth::user()->employee->departemen ?? '-' }}" disabled>
                            </div>
                        </div>

                        <hr class="my-4">
                        
                        <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-geo-alt-fill me-2"></i>Kontak & Alamat</h5>
  
                        <div class="mb-3">
                            <label for="alamat" class="form-label fw-bold text-muted small">Alamat Rumah</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="2" placeholder="Masukkan alamat lengkap...">{{ Auth::user()->employee->alamat ?? '' }}</textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="no_telepon" class="form-label fw-bold text-muted small">Nomor Telepon / WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" id="no_telepon" name="no_telepon" value="{{ Auth::user()->employee->no_telepon ?? '' }}" placeholder="Contoh: 081234567890">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-5">
                            <a href="{{ route('karyawan.dashboard') }}" class="btn btn-light text-muted px-4">Batalkan</a>
                            <button type="submit" class="btn btn-warning text-white px-4 fw-bold shadow-sm">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus Foto --}}
    <div class="modal fade" id="deleteFotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <div class="text-danger mb-3">
                        <i class="bi bi-exclamation-circle display-1"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Hapus Foto Profil?</h5>
                    <p class="text-muted small">Foto akan dihapus permanen dan diganti dengan inisial nama.</p>
                    
                    <form action="{{ route('karyawan.profil.deleteFoto') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    :root { --primary-color: #0d6efd; }
    .text-primary { color: var(--primary-color) !important; }
    .btn-outline-primary { color: var(--primary-color); border-color: var(--primary-color); }
    .btn-outline-primary:hover { background-color: var(--primary-color); color: white; }
    .bg-primary { background-color: var(--primary-color) !important; }
    
    @media (max-width: 768px) {
        .col-md-4 { border-right: none !important; border-bottom: 1px solid #dee2e6; padding-bottom: 2rem; margin-bottom: 2rem !important; }
        .col-md-4[style*="min-height"] { min-height: auto !important; }
        .ps-md-5 { padding-left: 0.75rem !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    document.getElementById('foto_input').addEventListener('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            const container = document.getElementById('foto-preview-container');
            container.innerHTML = ''; 
            const newImg = document.createElement('img');
            newImg.src = URL.createObjectURL(file);
            newImg.className = "rounded-circle shadow-lg object-fit-cover";
            newImg.style.width = "280px"; // Ukuran diperbesar
            newImg.style.height = "280px"; // Ukuran diperbesar
            newImg.style.border = "5px solid #fff";
            container.appendChild(newImg);
        }
    });
</script>
@endpush