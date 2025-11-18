@extends('layouts.app')

@section('page-title', 'Pengaturan Profil')

@section('content')
    <div class="container-fluid">
        
        {{-- Tampilkan Pesan Sukses --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                {{-- Form perlu enctype="multipart/form-data" untuk upload foto --}}
                <form action="{{ route('karyawan.profil.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        
                        {{-- KOLOM KIRI: FOTO PROFIL --}}
                        <div class="col-md-4 text-center d-flex flex-column align-items-center mb-4 mb-md-0">
                            <div class="position-relative mb-3 d-flex justify-content-center">
                                {{-- Tampilkan foto dari DB atau placeholder default --}}
                                @if($employee && $employee->foto_profil)
                                    <div style="width: 200px; height: 200px; overflow: hidden; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);">
                                        <img src="{{ asset('storage/' . $employee->foto_profil) }}" 
                                            class="img-fluid"
                                            alt="Foto Profil" 
                                            style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                                    </div>
                                @else
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($employee->nama ?? 'User') }}&background=0D8ABC&color=fff&size=200" 
                                        class="rounded-circle shadow-sm" 
                                        alt="Foto Default"
                                        style="width: 200px; height: 200px;">
                                @endif
                            </div>

                            {{-- Input File Tersembunyi & Tombol Custom --}}
                            <label for="fotoInput" class="btn btn-primary">
                                <i class="bi bi-camera-fill me-2"></i> Ganti Foto
                            </label>
                            <input type="file" id="fotoInput" name="foto_profil" class="d-none" onchange="previewImage(this)">
                            <small class="text-muted mt-2">Format: JPG, PNG (Max 2MB)</small>
                        </div>

                        {{-- KOLOM KANAN: DATA DIRI --}}
                        <div class="col-md-8">
                            {{-- Email (Readonly dari tabel User) --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Alamat Email</label>
                                <input type="email" class="form-control bg-light" value="{{ $user->email ?? '-' }}" readonly>
                            </div>

                            {{-- Nama (Readonly dari tabel Employee) --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Nama Lengkap</label>
                                <input type="text" class="form-control bg-light" value="{{ $employee->nama ?? '-' }}" readonly>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-muted">Jabatan</label>
                                    <input type="text" class="form-control bg-light" value="{{ $employee->posisi ?? '-' }}" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-muted">Departemen</label>
                                    <input type="text" class="form-control bg-light" value="{{ $employee->departemen ?? '-' }}" disabled>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h5 class="mb-3">Informasi Kontak</h5>

                            {{-- Alamat (Editable) --}}
                            <div class="mb-3">
                                <label for="alamatRumah" class="form-label">Alamat Rumah</label>
                                <textarea class="form-control @error('alamat') is-invalid @enderror" 
                                          id="alamatRumah" name="alamat" rows="2" 
                                          placeholder="Masukkan alamat lengkap...">{{ old('alamat', $employee->alamat ?? '') }}</textarea>
                                @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- No Telepon (Editable) --}}
                            <div class="mb-3">
                                <label for="nomorTelepon" class="form-label">Nomor Telepon</label>
                                <input type="text" class="form-control @error('no_telepon') is-invalid @enderror" 
                                       id="nomorTelepon" name="no_telepon" 
                                       value="{{ old('no_telepon', $employee->no_telepon ?? '') }}"
                                       placeholder="Contoh: 08123456789">
                                @error('no_telepon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="text-end mt-4">
                                <button type="reset" class="btn btn-outline-danger me-2">Reset</button>
                                <button type="submit" class="btn btn-warning text-dark fw-bold">
                                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Script sederhana untuk preview foto sebelum di-upload
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                // Mencari elemen img di kolom kiri dan mengubah src-nya
                input.closest('.col-md-4').querySelector('img').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush