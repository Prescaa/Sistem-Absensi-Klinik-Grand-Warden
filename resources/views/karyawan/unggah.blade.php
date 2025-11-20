@extends('layouts.app')

@section('page-title', 'Unggah Foto Absensi')

@section('content')
<div class="container-fluid">
    {{-- Data Work Area untuk JS (Disembunyikan) --}}
    @if(isset($workArea))
        <div id="work-area-data"
             data-lat="{{ $workArea->latitude }}"
             data-lng="{{ $workArea->longitude }}"
             data-rad="{{ $workArea->radius_geofence }}"></div>
    @endif

    <div class="row">
        <div class="col-12">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4 text-center">
                    @php
                        $sedangIzin = isset($todayLeave) && $todayLeave;
                        $selesaiAbsen = $absensiMasuk && $absensiPulang;
                        $isDisabled = $sedangIzin || $selesaiAbsen;
                        $karyawanId = auth()->user()->employee->emp_id;
                        $today = \Carbon\Carbon::today();

                        $rejectedToday = \App\Models\Attendance::where('emp_id', $karyawanId)
                            ->whereDate('waktu_unggah', $today)
                            ->whereHas('validation', function($q) {
                                $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
                            })->first();
                    @endphp

                    @if($sedangIzin)
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info-emphasis mb-0 d-inline-block px-5">
                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                            <strong>Anda sedang dalam masa {{ ucfirst($todayLeave->tipe_izin) }}.</strong>
                        </div>
                    @elseif($selesaiAbsen)
                        <div class="py-2">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                            <h3 class="fw-bold text-success">Absensi Hari Ini Selesai</h3>
                        </div>
                    @elseif($rejectedToday)
                        <div class="alert alert-danger mx-4 mt-4 mb-0">
                            <i class="bi bi-x-circle-fill me-2"></i>
                            <strong>Absensi Ditolak.</strong> Silakan unggah foto baru yang valid.
                        </div>
                    @else
                        <div class="alert alert-secondary border-0 d-inline-flex text-start px-4 py-3">
                            <i class="bi bi-shield-lock-fill fs-3 me-3 mt-1 text-primary"></i>
                            <div>
                                <strong>VERIFIKASI GANDA:</strong>
                                <ul class="mb-0 ps-3 small mt-1 text-muted">
                                    <li><strong>Lokasi File:</strong> Foto harus memiliki data lokasi (GPS) di area kantor.</li>
                                    <li><strong>Lokasi Anda:</strong> Anda wajib berada di kantor saat menekan tombol kirim.</li>
                                </ul>
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

                    {{-- FORM UPLOAD --}}
                    <form action="{{ route('karyawan.absensi.storeFoto') }}" method="POST" enctype="multipart/form-data" id="uploadForm" class="h-100">
                        @csrf
                        {{-- Input Type Hidden (Diisi oleh JS saat tombol diklik) --}}
                        <input type="hidden" name="type" id="attendanceType">

                        <div class="row g-0">
                            {{-- Kolom Kiri: Upload Area --}}
                            <div class="col-lg-8 border-end">
                                <div class="upload-area d-flex flex-column justify-content-center align-items-center text-center p-5" id="dropZone" style="min-height: 600px; cursor: pointer; background-color: #f8f9fa;">

                                    <input type="file" id="foto_absensi" name="foto_absensi" class="d-none" accept="image/jpeg,image/png" required>

                                    <div id="uploadPlaceholder">
                                        <div class="mb-4 p-4 rounded-circle bg-white shadow-sm d-inline-block">
                                            <i class="bi bi-geo-alt-fill text-primary" style="font-size: 5rem;"></i>
                                        </div>
                                        <h3 class="fw-bold mb-2">Pilih Foto Bukti</h3>
                                        <p class="text-muted mb-4">Foto harus mengandung data lokasi (GPS Tag)</p>
                                        <button type="button" class="btn btn-primary btn-lg px-5 rounded-pill shadow-sm">
                                            <i class="bi bi-folder2-open me-2"></i> Cari File
                                        </button>
                                    </div>

                                    <div id="previewContainer" class="d-none position-relative w-100 h-100 d-flex align-items-center justify-content-center bg-dark rounded-3 shadow-inner" style="max-height: 550px; overflow:hidden;">
                                        <img id="imagePreview" src="#" alt="Preview" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        <div class="position-absolute bottom-0 start-0 w-100 p-3 bg-dark bg-opacity-75 text-white d-flex justify-content-between align-items-center">
                                            <span class="small text-truncate" id="fileNameDisplay">Nama File...</span>
                                            <button type="button" class="btn btn-sm btn-danger rounded-pill px-3" id="removeFile">
                                                <i class="bi bi-trash-fill me-1"></i> Ganti
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Kolom Kanan: Tombol Aksi --}}
                            <div class="col-lg-4 bg-white d-flex flex-column justify-content-center p-5 border-start-lg position-relative">

                                {{-- Loading Overlay --}}
                                <div id="locationLoading" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-90 d-flex flex-column justify-content-center align-items-center d-none" style="z-index: 10;">
                                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                                    <h5 class="fw-bold text-dark">Memverifikasi Lokasi...</h5>
                                    <p class="text-muted small">Mohon tunggu sebentar</p>
                                </div>

                                <h4 class="fw-bold mb-4 text-center text-dark">Konfirmasi Kehadiran</h4>
                                <div class="d-grid gap-3">
                                    @if(is_null($absensiMasuk))
                                        {{-- Tombol Masuk --}}
                                        <button type="button" onclick="startAbsensi('masuk')" class="btn btn-primary btn-lg py-3 shadow-sm transition-hover">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="bi bi-box-arrow-in-right me-2 fs-4"></i>
                                                <span class="fw-bold">Absen Masuk</span>
                                            </div>
                                        </button>
                                        <button type="button" class="btn btn-light btn-lg py-3 text-muted border" disabled><i class="bi bi-lock-fill me-2"></i> Absen Pulang</button>
                                    @else
                                        <div class="alert alert-success border-0 d-flex align-items-center"><i class="bi bi-check-circle-fill fs-4 me-3"></i><div><small class="d-block fw-bold">STATUS</small>Sudah Masuk</div></div>
                                        @if(is_null($absensiPulang))
                                            {{-- Tombol Pulang --}}
                                            <button type="button" onclick="startAbsensi('pulang')" class="btn btn-danger btn-lg py-3 shadow-sm mt-2 transition-hover">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-box-arrow-right me-2 fs-4"></i>
                                                    <span class="fw-bold">Absen Pulang</span>
                                                </div>
                                            </button>
                                        @else
                                            <div class="alert alert-success border-0 d-flex align-items-center mt-2"><i class="bi bi-check-circle-fill fs-4 me-3"></i><div><small class="d-block fw-bold">STATUS</small>Sudah Pulang</div></div>
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
    .upload-area { transition: all 0.2s ease-in-out; }
    .upload-area.dragover { background-color: #e2e6ea !important; border: 2px dashed var(--primary-color); }
    .btn-lg { border-radius: 10px; }
    .transition-hover:hover { transform: translateY(-2px); }
</style>
@endpush

@push('scripts')
<script>
    // --- VARIABLE GLOBAL ---
    const workAreaEl = document.getElementById('work-area-data');
    const OFFICE_LAT = workAreaEl ? parseFloat(workAreaEl.dataset.lat) : 0;
    const OFFICE_LNG = workAreaEl ? parseFloat(workAreaEl.dataset.lng) : 0;
    const OFFICE_RAD = workAreaEl ? parseFloat(workAreaEl.dataset.rad) : 50;

    // --- LOGIKA UPLOAD FILE (PREVIEW) ---
    // (Bagian ini sama seperti sebelumnya, untuk preview gambar)
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('foto_absensi');
    const previewContainer = document.getElementById('previewContainer');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const imagePreview = document.getElementById('imagePreview');
    const removeFileBtn = document.getElementById('removeFile');
    const fileNameDisplay = document.getElementById('fileNameDisplay');

    function handleFile(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                fileNameDisplay.textContent = file.name;
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

    removeFileBtn.addEventListener('click', (e) => {
        e.stopPropagation(); fileInput.value = ''; imagePreview.src = '#';
        previewContainer.classList.add('d-none'); uploadPlaceholder.classList.remove('d-none');
    });

    // ==========================================
    // --- LOGIKA ABSENSI BERJENJANG (LAYER) ---
    // ==========================================

    function startAbsensi(type) {
        const form = document.getElementById('uploadForm');
        const loading = document.getElementById('locationLoading'); // Pastikan elemen ini ada di HTML Anda
        const loadingText = loading.querySelector('h5'); // Untuk ubah teks loading
        const inputType = document.getElementById('attendanceType');

        // Cek apakah file sudah dipilih
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Harap pilih foto bukti absensi terlebih dahulu!');
            return;
        }

        // Set tipe absen
        inputType.value = type;

        // Tampilkan Loading Awal
        loading.classList.remove('d-none');
        if(loadingText) loadingText.innerText = "LAYER 1: Memeriksa Validitas Foto...";

        // --- LAYER 1: Cek EXIF ke Server via AJAX ---
        const formData = new FormData();
        formData.append('foto_absensi', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}'); // CSRF Token Wajib

        fetch('{{ route("karyawan.absensi.checkExif") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'error') {
                // GAGAL LAYER 1 (EXIF/Hash Salah)
                throw new Error(data.message);
            }

            // BERHASIL LAYER 1 -> LANJUT LAYER 2
            if(loadingText) loadingText.innerText = "LAYER 2: Memverifikasi Lokasi Anda...";

            // Delay sedikit agar transisi teks terbaca (opsional)
            return new Promise(resolve => setTimeout(resolve, 500));
        })
        .then(() => {
            // --- LAYER 2: Cek Geolocation Browser ---
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        const distance = haversineDistance(userLat, userLng, OFFICE_LAT, OFFICE_LNG);

                        console.log(`Jarak User: ${distance}m`);

                        if (distance <= OFFICE_RAD) {
                            // LOLOS LAYER 2 -> SUBMIT FINAL
                            if(loadingText) loadingText.innerText = "Menyimpan Data Absensi...";
                            form.submit();
                        } else {
                            // GAGAL LAYER 2 (Diluar Jangkauan)
                            loading.classList.add('d-none');
                            alert(`GAGAL LAYER 2: Posisi Anda terdeteksi sejauh ${Math.round(distance)}m dari kantor. Maksimal radius: ${OFFICE_RAD}m.`);
                        }
                    },
                    (error) => {
                        // GAGAL LAYER 2 (Error GPS Browser)
                        loading.classList.add('d-none');
                        let msg = "Gagal mengambil lokasi browser.";
                        if (error.code == error.PERMISSION_DENIED) msg = "Izin lokasi ditolak browser. Anda wajib mengizinkan lokasi untuk absen.";
                        alert(msg);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                loading.classList.add('d-none');
                alert("Browser tidak mendukung Geolocation.");
            }
        })
        .catch(error => {
            // Tangkap Error dari Layer 1 atau Fetch
            loading.classList.add('d-none');
            alert("GAGAL LAYER 1: " + error.message);
        });
    }

    // Rumus Haversine
    function haversineDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
</script>
@endpush
