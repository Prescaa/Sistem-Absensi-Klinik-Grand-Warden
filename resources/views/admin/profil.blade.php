@extends('layouts.admin_app')

@section('page-title', 'Pengaturan Profil Admin')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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
            {{-- Form Update Profil Admin --}}
            <form action="{{ route('admin.profil.update') }}" method="POST" enctype="multipart/form-data" id="updateForm">
                @csrf
                
                <div class="row align-items-center">
                    
                    {{-- KOLOM KIRI: FOTO --}}
                    <div class="col-md-4 text-center d-flex flex-column justify-content-center align-items-center mb-5 mb-md-0" style="border-right: 1px solid #dee2e6; min-height: 400px;">
                        <div class="position-relative mb-4">
                            <div id="foto-preview-container">
                                @if(isset($employee->foto_profil) && $employee->foto_profil)
                                    <img src="{{ asset($employee->foto_profil) }}" class="rounded-circle shadow-lg object-fit-cover" alt="Foto Profil" style="width: 280px; height: 280px; border: 5px solid #fff;">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-lg" style="width: 280px; height: 280px; font-size: 7rem; border: 5px solid #fff;">
                                        {{ strtoupper(substr($employee->nama ?? Auth::user()->username, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <input type="file" id="foto_input" name="foto_profil" class="d-none" accept="image/*">
                        </div>
                        
                        <div class="d-flex gap-2">
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

                    {{-- KOLOM KANAN: DATA --}}
                    <div class="col-md-8 ps-md-5">
                        <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Informasi Pribadi</h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" value="{{ $employee->nama ?? Auth::user()->username }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">NIP</label>
                                <input type="text" class="form-control" name="nip" value="{{ $employee->nip ?? '' }}" placeholder="NIP">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Username</label>
                            <input type="text" class="form-control" name="username" value="{{ Auth::user()->username }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ Auth::user()->email }}" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Jabatan</label>
                                <input type="text" class="form-control" name="posisi" value="{{ $employee->posisi ?? 'Administrator' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Departemen</label>
                                <input type="text" class="form-control" name="departemen" value="{{ $employee->departemen ?? 'Manajemen' }}">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-geo-alt-fill me-2"></i>Kontak & Alamat</h5>
  
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">Alamat Rumah</label>
                            <textarea class="form-control" name="alamat" rows="2">{{ $employee->alamat ?? '' }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small">Nomor Telepon</label>
                            <input type="text" class="form-control" name="no_telepon" value="{{ $employee->no_telepon ?? '' }}">
                        </div>

                        {{-- ✅ PERBAIKAN: Hidden input untuk status hapus foto --}}
                        <input type="hidden" name="hapus_foto" id="hapus_foto" value="0">

                        <div class="d-flex justify-content-end gap-2 mt-5">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-light text-muted px-4">Batalkan</a>
                            <button type="submit" class="btn btn-warning text-white px-4 fw-bold shadow-sm">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Hapus Foto --}}
    <div class="modal fade" id="deleteFotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <div class="text-danger mb-3"><i class="bi bi-exclamation-circle display-1"></i></div>
                    <h5 class="fw-bold mb-2">Hapus Foto Profil?</h5>
                    <p class="text-muted small">Foto akan dihapus ketika Anda menekan "Simpan Perubahan".</p>
                    
                    {{-- ✅ PERBAIKAN: Bukan form terpisah, tapi trigger JavaScript --}}
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-danger" onclick="setHapusFoto()" data-bs-dismiss="modal">Ya, Hapus</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    </div>
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
            newImg.style.width = "280px";
            newImg.style.height = "280px";
            newImg.style.border = "5px solid #fff";
            container.appendChild(newImg);
            
            // Reset status hapus foto jika upload foto baru
            document.getElementById('hapus_foto').value = '0';
        }
    });

    // ✅ PERBAIKAN: Function untuk set status hapus foto
    function setHapusFoto() {
        document.getElementById('hapus_foto').value = '1';
        
        // Update preview untuk menunjukkan foto akan dihapus
        const container = document.getElementById('foto-preview-container');
        container.innerHTML = `
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-lg" style="width: 280px; height: 280px; font-size: 7rem; border: 5px solid #fff; opacity: 0.7; position: relative;">
                ${'{{ strtoupper(substr($employee->nama ?? Auth::user()->username, 0, 1)) }}'}
                <div class="position-absolute top-0 start-0 w-100 h-100 bg-danger bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-trash-fill text-white fs-1"></i>
                </div>
            </div>
        `;
        
        // Reset file input
        document.getElementById('foto_input').value = '';
        
        // Tampilkan alert bahwa foto akan dihapus saat simpan
        alert('Foto akan dihapus ketika Anda menekan "Simpan Perubahan".');
    }
</script>
@endpush