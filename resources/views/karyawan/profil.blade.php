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

    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            {{-- Form mengarah ke route update profil --}}
            <form action="{{ route('karyawan.profil.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT') {{-- Gunakan method PUT untuk update --}}
                
                <div class="row">
                    {{-- Kolom Kiri: Foto Profil --}}
                    <div class="col-md-4 text-center d-flex flex-column align-items-center border-end-md mb-4 mb-md-0">
                        <div class="position-relative mb-3">
                            @if(isset($employee->foto_profil) && $employee->foto_profil)
                                <img src="{{ asset($employee->foto_profil) }}" class="rounded-circle shadow-sm object-fit-cover" alt="Foto Profil" style="width: 200px; height: 200px;">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 200px; height: 200px; font-size: 4rem;">
                                    {{ strtoupper(substr(Auth::user()->employee->nama ?? Auth::user()->username, 0, 1)) }}
                                </div>
                            @endif
                            
                            {{-- Tombol Upload Tersembunyi --}}
                            <label for="foto_input" class="position-absolute bottom-0 end-0 bg-white rounded-circle p-2 shadow cursor-pointer" style="cursor: pointer;">
                                <i class="bi bi-camera-fill text-primary fs-4"></i>
                            </label>
                            <input type="file" id="foto_input" name="foto_profil" class="d-none" accept="image/*">
                        </div>
                        
                        <h5 class="fw-bold">{{ Auth::user()->employee->nama }}</h5>
                        <p class="text-muted small">{{ Auth::user()->employee->posisi ?? 'Karyawan' }}</p>
                        
                        <div class="mt-2">
                             <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('foto_input').click()">
                                <i class="bi bi-upload me-1"></i> Ganti Foto
                            </button>
                        </div>
                    </div>

                    {{-- Kolom Kanan: Form Data --}}
                    <div class="col-md-8 ps-md-5">
                        <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Informasi Pribadi</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Nama Lengkap</label>
                                <input type="text" class="form-control bg-light" value="{{ Auth::user()->employee->nama }}" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">NIP</label>
                                <input type="text" class="form-control bg-light" value="{{ Auth::user()->employee->nip ?? '-' }}" readonly disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Alamat Email</label>
                            <input type="email" class="form-control bg-light" value="{{ Auth::user()->email }}" readonly disabled>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Jabatan</label>
                                <input type="text" class="form-control bg-light" value="{{ Auth::user()->employee->posisi ?? '-' }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Departemen</label>
                                <input type="text" class="form-control bg-light" value="{{ Auth::user()->employee->departemen ?? '-' }}" disabled>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-geo-alt-fill me-2"></i>Kontak & Alamat</h5>
                        
                        <div class="mb-3">
                            <label for="alamatRumah" class="form-label fw-semibold">Alamat Rumah</label>
                            <textarea class="form-control" id="alamatRumah" name="alamat_rumah" rows="2" placeholder="Masukkan alamat lengkap...">{{ Auth::user()->employee->alamat_rumah ?? '' }}</textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="nomorTelepon" class="form-label fw-semibold">Nomor Telepon / WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" id="nomorTelepon" name="nomor_telepon" value="{{ Auth::user()->employee->nomor_telepon ?? '' }}" placeholder="Contoh: 081234567890">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('karyawan.dashboard') }}" class="btn btn-light text-muted px-4">Batalkan</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                <i class="bi bi-save me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* KONSISTENSI WARNA: Menggunakan variabel CSS utama */
    :root {
        --primary-color: #0d6efd; /* Biru Bootstrap standar, bisa diganti kode hex Figma */
    }
    
    .text-primary { color: var(--primary-color) !important; }
    .btn-primary { 
        background-color: var(--primary-color); 
        border-color: var(--primary-color);
    }
    .btn-primary:hover {
        background-color: #0b5ed7; /* Versi sedikit lebih gelap untuk hover */
        border-color: #0a58ca;
    }
    .btn-outline-primary {
        color: var(--primary-color);
        border-color: var(--primary-color);
    }
    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: white;
    }
    .bg-primary { background-color: var(--primary-color) !important; }
    
    /* Responsif border untuk desktop */
    @media (min-width: 768px) {
        .border-end-md {
            border-right: 1px solid #dee2e6;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Preview gambar saat dipilih
    document.getElementById('foto_input').addEventListener('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            // Cari elemen gambar (baik img tag atau div inisial)
            const imgContainer = this.parentElement.querySelector('img, .avatar-initial');
            
            if (imgContainer.tagName === 'IMG') {
                imgContainer.src = URL.createObjectURL(file);
            } else {
                // Jika sebelumnya inisial, ganti jadi img
                const newImg = document.createElement('img');
                newImg.src = URL.createObjectURL(file);
                newImg.className = "rounded-circle shadow-sm object-fit-cover";
                newImg.style.width = "200px";
                newImg.style.height = "200px";
                imgContainer.replaceWith(newImg);
            }
        }
    });
</script>
@endpush