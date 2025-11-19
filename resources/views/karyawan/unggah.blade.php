@extends('layouts.app')

@section('page-title', 'Unggah Foto Absensi')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4 text-center">
                    @php
                        $sedangIzin = isset($todayLeave) && $todayLeave;
                        $selesaiAbsen = $absensiMasuk && $absensiPulang;
                        $isDisabled = $sedangIzin || $selesaiAbsen;
                    @endphp

                    @if($sedangIzin)
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info-emphasis mb-0 d-inline-block px-5">
                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                            <strong>Anda sedang dalam masa {{ ucfirst($todayLeave->tipe_izin) }}.</strong> Tidak perlu absen hari ini.
                        </div>
                    @elseif($selesaiAbsen)
                        <div class="py-2">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                            <h3 class="fw-bold text-success">Absensi Hari Ini Selesai</h3>
                            <p class="text-muted mb-0">Terima kasih atas kerja keras Anda.</p>
                        </div>
                    @else
                        <div>
                            <h4 class="fw-bold text-dark mb-2">Silakan Lakukan Absensi</h4>
                            <div class="alert alert-warning border-0 d-inline-flex align-items-center px-4 py-2">
                                <i class="bi bi-phone-vibrate fs-4 me-3"></i>
                                <div class="text-start">
                                    <strong>PENTING:</strong><br>
                                    Ambil foto langsung dari <u>Handphone/Ponsel</u> Anda agar data lokasi (GPS) terdeteksi akurat.
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if(!$isDisabled)
            <div class="card shadow-lg border-0">
                <div class="card-body p-0"> 
                    @if (session('error'))
                        <div class="alert alert-danger m-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                        </div>
                    @endif
                    
                    <form action="{{ route('karyawan.absensi.storeFoto') }}" method="POST" enctype="multipart/form-data" id="uploadForm" class="h-100">
                        @csrf
                        <div class="row g-0">
                            <div class="col-lg-8 border-end">
                                <div class="upload-area d-flex flex-column justify-content-center align-items-center text-center p-5" id="dropZone" style="min-height: 600px; cursor: pointer; background-color: #fcfcfc;">
                                    <input type="file" id="foto_absensi" name="foto_absensi" class="d-none" accept="image/jpeg,image/png" required>
                                    <div id="uploadPlaceholder">
                                        <div class="mb-4 p-4 rounded-circle bg-light d-inline-block">
                                            <i class="bi bi-cloud-arrow-up-fill text-primary" style="font-size: 6rem;"></i>
                                        </div>
                                        <h2 class="fw-bold mb-3">Seret & Lepas Foto di Sini</h2>
                                        <p class="text-muted fs-5 mb-4">atau klik di area ini untuk membuka kamera/galeri</p>
                                        <button type="button" class="btn btn-outline-primary btn-lg px-5 rounded-pill" onclick="document.getElementById('foto_absensi').click()">
                                            <i class="bi bi-camera-fill me-2"></i> Ambil Foto
                                        </button>
                                    </div>
                                    <div id="previewContainer" class="d-none position-relative w-100 h-100 d-flex align-items-center justify-content-center bg-dark rounded-3" style="max-height: 550px;">
                                        <img id="imagePreview" src="#" alt="Preview" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        <button type="button" class="btn btn-danger position-absolute top-0 end-0 m-3 rounded-circle p-3 shadow" id="removeFile" title="Hapus Foto">
                                            <i class="bi bi-x-lg fs-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 bg-light d-flex flex-column justify-content-center p-5">
                                <h4 class="fw-bold mb-4 text-center">Konfirmasi Absensi</h4>
                                <div class="d-grid gap-4">
                                    @if(is_null($absensiMasuk))
                                        <button type="submit" name="type" value="masuk" class="btn btn-primary btn-lg py-4 fs-4 shadow-sm">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="bi bi-box-arrow-in-right me-3 fs-2"></i> <span>Absen Masuk</span>
                                            </div>
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-lg py-3 disabled" style="opacity: 0.6"><i class="bi bi-lock-fill me-2"></i> Absen Pulang Terkunci</button>
                                    @else
                                        <button type="button" class="btn btn-success btn-lg py-3 disabled" style="opacity: 1"><i class="bi bi-check-circle-fill me-2"></i> Sudah Masuk</button>
                                        @if(is_null($absensiPulang))
                                            <button type="submit" name="type" value="pulang" class="btn btn-danger btn-lg py-4 fs-4 shadow-sm mt-3">
                                                <div class="d-flex align-items-center justify-content-center"><i class="bi bi-box-arrow-right me-3 fs-2"></i><span>Absen Pulang</span></div>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-success btn-lg py-3 disabled mt-3" style="opacity: 1"><i class="bi bi-check-circle-fill me-2"></i> Sudah Pulang</button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .upload-area { transition: all 0.3s ease; }
    .upload-area.dragover { background-color: #e9ecef !important; border: 2px dashed var(--primary-color); }
    .btn-lg { border-radius: 12px; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('foto_absensi');
        const previewContainer = document.getElementById('previewContainer');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        const imagePreview = document.getElementById('imagePreview');
        const removeFileBtn = document.getElementById('removeFile');

        function handleFile(file) {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    uploadPlaceholder.classList.add('d-none');
                    previewContainer.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        }
        fileInput.addEventListener('change', function(e) { handleFile(e.target.files[0]); });
        dropZone.addEventListener('click', (e) => { if(e.target !== removeFileBtn && !removeFileBtn.contains(e.target)) fileInput.click(); });
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); }, false));
        ['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.add('dragover'), false));
        ['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.remove('dragover'), false));
        dropZone.addEventListener('drop', (e) => { fileInput.files = e.dataTransfer.files; handleFile(e.dataTransfer.files[0]); });
        removeFileBtn.addEventListener('click', (e) => { e.stopPropagation(); fileInput.value = ''; imagePreview.src = '#'; previewContainer.classList.add('d-none'); uploadPlaceholder.classList.remove('d-none'); });
    });
</script>
@endpush