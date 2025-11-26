@extends('layouts.manajemen_app')

@section('page-title', 'Pengaturan Profil')

@section('content')

    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            {{-- Form Update Profil --}}
            <form action="{{ route('manajemen.profil.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
                @csrf
                @method('POST') 
                
                {{-- Input Hidden untuk Logika Hapus Foto --}}
                <input type="hidden" name="hapus_foto" id="hapus_foto_input" value="0">

                <div class="row align-items-center">
                    
                    {{-- KOLOM KIRI: FOTO PROFIL --}}
                    <div class="col-md-4 text-center d-flex flex-column justify-content-center align-items-center mb-5 mb-md-0" style="border-right: 1px solid #dee2e6; min-height: 400px;">
                        
                        <div class="position-relative mb-4">
                            {{-- Container Preview Foto --}}
                            <div id="foto-preview-container">
                                @if(isset($employee->foto_profil) && $employee->foto_profil)
                                    <img src="{{ asset($employee->foto_profil) }}" id="img-preview" class="rounded-circle shadow-lg object-fit-cover" alt="Foto Profil" style="width: 280px; height: 280px; border: 5px solid #fff;">
                                @else
                                    {{-- Placeholder Inisial --}}
                                    <div id="initial-preview" class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-lg" style="width: 280px; height: 280px; font-size: 7rem; border: 5px solid #fff;">
                                        {{ strtoupper(substr(Auth::user()->employee->nama ?? Auth::user()->username, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Input File Hidden --}}
                            <input type="file" id="foto_input" name="foto_profil" class="d-none" accept="image/*">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary px-4" onclick="document.getElementById('foto_input').click()">
                                <i class="bi bi-camera-fill me-2"></i> Ganti Foto
                            </button>

                            {{-- Tombol Trigger Modal Hapus --}}
                            <button type="button" class="btn btn-outline-danger" id="btn-hapus-trigger" 
                                    data-bs-toggle="modal" data-bs-target="#deleteFotoModal"
                                    style="{{ (isset($employee->foto_profil) && $employee->foto_profil) ? '' : 'display:none;' }}">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>

                    {{-- KOLOM KANAN: FORM DATA --}}
                    <div class="col-md-8 ps-md-5">
                        
                        <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Informasi Pribadi</h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control bg-white border" value="{{ Auth::user()->employee->nama }}" required>
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
                                <input type="number" class="form-control" id="no_telepon" name="no_telepon" 
                                       value="{{ Auth::user()->employee->no_telepon ?? '' }}" 
                                       placeholder="Contoh: 081234567890"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                            <div class="form-text small text-muted">Hanya boleh diisi angka.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-5">
                            <a href="{{ route('manajemen.dashboard') }}" class="btn btn-light text-muted px-4">Batalkan</a>
                            {{-- Tombol Submit Utama --}}
                            <button type="submit" class="btn btn-warning text-white px-4 fw-bold shadow-sm">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- 
        MODAL KONFIRMASI HAPUS 
        - Tanpa tag <form> agar tidak submit otomatis.
        - Tombol "Ya, Hapus" memicu JS untuk update tampilan & input hidden.
    --}}
    <div class="modal fade" id="deleteFotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0 text-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btn-close-modal-x"></button>
                </div>
                <div class="modal-body text-center pt-0 pb-4 px-4">
                    <div class="text-danger mb-3">
                        <i class="bi bi-exclamation-circle display-1"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Hapus Foto Profil?</h5>
                    <p class="text-muted small mb-4">Foto akan dihapus permanen dan diganti dengan inisial nama setelah Anda menyimpan perubahan.</p>
                    
                    <div class="d-grid gap-2">
                        {{-- Tombol Konfirmasi JS --}}
                        <button type="button" class="btn btn-danger fw-bold" id="btn-confirm-hapus">Ya, Hapus</button>
                        {{-- Tombol Batal Manual (Data Dismiss) --}}
                        <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal" id="btn-close-modal-batal">Batal</button>
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
    
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; margin: 0; 
    }
    
    @media (max-width: 768px) {
        .col-md-4 { border-right: none !important; border-bottom: 1px solid #dee2e6; padding-bottom: 2rem; margin-bottom: 2rem !important; }
        .col-md-4[style*="min-height"] { min-height: auto !important; }
        .ps-md-5 { padding-left: 0.75rem !important; }
    }

    /* CSS Dark Mode */
    .dark-mode .card {
        background-color: #1e1e1e !important;
        border-color: #333 !important;
        color: #e0e0e0;
    }
    .dark-mode .form-control {
        background-color: #2b2b2b !important;
        border-color: #444 !important;
        color: #fff !important;
    }
    .dark-mode .form-control:disabled, .dark-mode .form-control[readonly] {
        background-color: #333 !important; 
        color: #aaa !important;
    }
    .dark-mode .form-control:focus {
        border-color: #0d6efd !important;
    }
    .dark-mode .input-group-text {
        background-color: #333 !important;
        border-color: #444 !important;
        color: #fff !important;
    }
    .dark-mode .text-muted { color: #aaa !important; }
    .dark-mode .col-md-4[style*="border-right"] {
        border-right-color: #444 !important;
    }
    @media (max-width: 768px) {
        .dark-mode .col-md-4 { border-bottom-color: #444 !important; }
    }
    .dark-mode .modal-content {
        background-color: #1e1e1e !important;
        border-color: #444;
        color: #fff;
    }
    .dark-mode .btn-close { filter: invert(1); }
</style>
@endpush

@push('scripts')
<script>
    const fotoInput = document.getElementById('foto_input');
    const container = document.getElementById('foto-preview-container');
    const hapusInput = document.getElementById('hapus_foto_input');
    const btnHapusTrigger = document.getElementById('btn-hapus-trigger');
    const btnConfirmHapus = document.getElementById('btn-confirm-hapus');
    const btnBatal = document.getElementById('btn-close-modal-batal');

    // Template Inisial (Dipakai saat foto dihapus)
    const initialHTML = `
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-lg" style="width: 280px; height: 280px; font-size: 7rem; border: 5px solid #fff;">
            {{ strtoupper(substr(Auth::user()->employee->nama ?? Auth::user()->username, 0, 1)) }}
        </div>
    `;

    // 1. Event Ganti Foto (Upload Baru)
    fotoInput.addEventListener('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            // Reset flag hapus (karena user upload foto baru)
            hapusInput.value = "0";
            
            // Render Preview Gambar
            container.innerHTML = ''; 
            const newImg = document.createElement('img');
            newImg.src = URL.createObjectURL(file);
            newImg.className = "rounded-circle shadow-lg object-fit-cover";
            newImg.style.width = "280px"; 
            newImg.style.height = "280px"; 
            newImg.style.border = "5px solid #fff";
            container.appendChild(newImg);

            // Tampilkan tombol hapus (tong sampah)
            if(btnHapusTrigger) btnHapusTrigger.style.display = 'inline-block';
        }
    });

    // 2. Event Klik "Ya, Hapus" di dalam Modal
    if(btnConfirmHapus) {
        btnConfirmHapus.addEventListener('click', function() {
            // a. Set Flag Hapus menjadi 1 (Agar Controller tahu)
            hapusInput.value = "1";
            
            // b. Reset input file (jika ada file terpilih sebelumnya)
            fotoInput.value = '';

            // c. Ganti Preview menjadi Inisial
            container.innerHTML = initialHTML;

            // d. Sembunyikan tombol hapus (tong sampah)
            if(btnHapusTrigger) btnHapusTrigger.style.display = 'none';

            // e. Tutup Modal dengan cara "Klik Tombol Batal" secara programatis
            // Ini solusi paling aman tanpa perlu instance Bootstrap manual
            if(btnBatal) {
                btnBatal.click();
            } else {
                // Fallback jika tombol batal tidak ketemu (misal diubah ID-nya)
                const closeModalX = document.getElementById('btn-close-modal-x');
                if(closeModalX) closeModalX.click();
            }
        });
    }
</script>
@endpush