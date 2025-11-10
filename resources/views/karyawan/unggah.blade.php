@extends('layouts.app')

@section('page-title', 'Unggah Absensi')

@section('content')
    <div class="row mb-4">
        <!-- Card Nama Karyawan -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                            <i class="bi bi-person-fill text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted small mb-1">Nama Karyawan</h6>
                            <h5 class="fw-bold mb-0">Mahardika</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Jam -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                            <i class="bi bi-clock-fill text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted small mb-1">Jam</h6>
                            <h5 class="fw-bold mb-0" id="current-time">12:16</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Tanggal -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                            <i class="bi bi-calendar-fill text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted small mb-1">Tanggal</h6>
                            <h5 class="fw-bold mb-0" id="current-date">Jum, 11 April 2025</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Lokasi -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                            <i class="bi bi-geo-alt-fill text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted small mb-1">Lokasi</h6>
                            <h5 class="fw-bold mb-0">Jl. Medan Merdeka T.</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Area Unggah File -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            <form action="/upload-absensi" method="POST" enctype="multipart/form-data" id="uploadForm">
                @csrf
                <div class="text-center upload-area" id="uploadArea" 
                     style="border: 2px dashed #ddd; padding: 5rem; border-radius: .5rem; cursor: pointer; transition: all 0.3s ease;">
                    <i class="bi bi-cloud-arrow-up-fill text-primary" style="font-size: 6rem;"></i>
                    <h4 class="my-3">Seret atau jatuhkan file untuk mengunggah</h4>
                    <p class="text-muted mb-3">Format yang didukung: JPG, PNG, PDF (Maks. 5MB)</p>
                    
                    <!-- Input file yang tersembunyi -->
                    <input type="file" name="absensi_file" id="fileInput" accept=".jpg,.jpeg,.png,.pdf" style="display: none;">
                    
                    <button type="button" class="btn btn-primary btn-lg" id="browseButton">
                        <i class="bi bi-folder2-open me-2"></i>Browse
                    </button>
                    
                    <!-- Preview file -->
                    <div id="filePreview" class="mt-3" style="display: none;">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            <span id="fileName"></span>
                            <button type="button" class="btn-close ms-2" id="removeFile"></button>
                        </div>
                    </div>
                </div>
                
                <!-- Submit button (akan muncul setelah file dipilih) -->
                <div class="text-center mt-4" id="submitArea" style="display: none;">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-upload me-2"></i>Unggah Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .upload-area:hover {
        border-color: #0d6efd !important;
        background-color: #f8f9fa;
    }
    .upload-area.dragover {
        border-color: #0d6efd !important;
        background-color: #e3f2fd;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const browseButton = document.getElementById('browseButton');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const removeFile = document.getElementById('removeFile');
        const submitArea = document.getElementById('submitArea');
        const uploadForm = document.getElementById('uploadForm');

        // Update waktu real-time
        function updateDateTime() {
            const now = new Date();
            
            // Format jam
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}`;
            
            // Format tanggal
            const options = { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' };
            const dateStr = now.toLocaleDateString('id-ID', options);
            document.getElementById('current-date').textContent = dateStr;
        }
        
        updateDateTime();
        setInterval(updateDateTime, 60000); // Update setiap menit

        // Browse button click
        browseButton.addEventListener('click', function() {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', function(e) {
            handleFileSelection(e.target.files[0]);
        });

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelection(files[0]);
            }
        });

        // Handle file selection
        function handleFileSelection(file) {
            if (file) {
                // Validasi file
                const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!validTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan JPG, PNG, atau PDF.');
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File terlalu besar. Maksimal 5MB.');
                    return;
                }
                
                // Tampilkan preview
                fileName.textContent = file.name;
                filePreview.style.display = 'block';
                submitArea.style.display = 'block';
            }
        }

        // Remove file
        removeFile.addEventListener('click', function() {
            fileInput.value = '';
            filePreview.style.display = 'none';
            submitArea.style.display = 'none';
        });

        // Form submission
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!fileInput.files[0]) {
                alert('Pilih file terlebih dahulu.');
                return;
            }
            
            // Simulasi upload (dalam implementasi real, ini akan mengirim ke server)
            const submitBtn = uploadForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner-border spinner-border-sm me-2"></i>Mengunggah...';
            submitBtn.disabled = true;
            
            // Simulasi proses upload
            setTimeout(function() {
                alert('File berhasil diunggah!');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                fileInput.value = '';
                filePreview.style.display = 'none';
                submitArea.style.display = 'none';
            }, 2000);
        });
    });
</script>
@endpush