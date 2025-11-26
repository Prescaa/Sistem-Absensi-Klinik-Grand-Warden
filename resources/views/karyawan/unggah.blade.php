@extends('layouts.app')

@section('page-title', 'Unggah Foto Absensi')

@section('content')
<div class="container-fluid">
    {{-- Data Work Area untuk JS (Dikirim dari Controller) --}}
    @if(isset($workArea))
        {{-- 
            UPDATE: Mengambil data latitude/longitude yang sudah diparsing 
            dengan benar di Controller (ST_Y=Lat, ST_X=Lng) 
        --}}
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
                        
                        // Cek apakah ada absensi hari ini yang ditolak/invalid
                        $rejectedToday = \App\Models\Attendance::where('emp_id', $karyawanId)
                            ->whereDate('waktu_unggah', $today)
                            ->whereHas('validation', function($q) {
                                $q->whereIn('status_validasi_final', ['Invalid', 'Rejected']);
                            })->first();
                        
                        // Ambil Jam Kerja dari WorkArea (dikirim dari Controller)
                        // Pastikan handling jika workArea null atau jam_kerja kosong
                        $jamKerja = $workArea->jam_kerja ?? [];
                        $jamMasuk = $jamKerja['masuk'] ?? '08:00';
                        $jamPulang = $jamKerja['pulang'] ?? '17:00';
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
                    @elseif($rejectedToday)
                        <div class="alert alert-danger mx-4 mt-4 mb-0">
                            <i class="bi bi-x-circle-fill me-2"></i>
                            <strong>Perhatian:</strong> Absensi {{ ucfirst($rejectedToday->type) }} Anda hari ini ditolak.
                            <br>Silakan ambil foto baru yang lebih jelas dan sesuai lokasi.
                        </div>
                    @else
                        <div>
                            <h4 class="fw-bold text-dark mb-2">Silakan Lakukan Absensi</h4>
                            
                            {{-- INFORMASI JAM KERJA & SIZE --}}
                            <div class="d-flex justify-content-center gap-3 mb-3 flex-wrap">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2">
                                    <i class="bi bi-clock me-1"></i> Masuk Maks: <strong>{{ $jamMasuk }}</strong>
                                </span>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2">
                                    <i class="bi bi-clock-history me-1"></i> Pulang Min: <strong>{{ $jamPulang }}</strong>
                                </span>
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning text-dark px-3 py-2">
                                    <i class="bi bi-file-earmark-image me-1"></i> Max Size: <strong>10MB</strong>
                                </span>
                            </div>

                            <div class="alert alert-warning border-0 d-inline-flex align-items-center px-4 py-2">
                                <i class="bi bi-phone-vibrate fs-4 me-3"></i>
                                <div class="text-start small">
                                    <strong>PENTING:</strong><br>
                                    Pastikan wajah terlihat jelas & lokasi (GPS) aktif.<br>
                                    Sistem akan memverifikasi lokasi foto (EXIF) dan lokasi HP Anda.
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
                        {{-- Hidden Inputs untuk Data --}}
                        <input type="hidden" name="type" id="attendanceType">
                        <input type="hidden" name="browser_lat" id="browser_lat">
                        <input type="hidden" name="browser_lng" id="browser_lng">

                        <div class="row g-0">
                            {{-- Kolom Kiri: Upload Area --}}
                            <div class="col-lg-8 border-end">
                                <div class="upload-area d-flex flex-column justify-content-center align-items-center text-center p-5" id="dropZone" style="min-height: 600px; cursor: pointer; background-color: #fcfcfc;">

                                    <input type="file" id="foto_absensi" name="foto_absensi" class="d-none" accept="image/jpeg,image/png" required>

                                    {{-- Placeholder sebelum upload --}}
                                    <div id="uploadPlaceholder">
                                        <div class="mb-4 p-4 rounded-circle bg-light d-inline-block">
                                            <i class="bi bi-cloud-arrow-up-fill text-primary" style="font-size: 6rem;"></i>
                                        </div>
                                        <h2 class="fw-bold mb-3">Seret & Lepas Foto di Sini</h2>
                                        <p class="text-muted fs-5 mb-4">atau klik di area ini untuk membuka kamera/galeri</p>
                                        <button type="button" class="btn btn-outline-primary btn-lg px-5 rounded-pill">
                                            <i class="bi bi-camera-fill me-2"></i> Ambil Foto
                                        </button>
                                    </div>

                                    {{-- Container Preview setelah upload --}}
                                    <div id="previewContainer" class="d-none position-relative w-100 h-100 d-flex align-items-center justify-content-center bg-dark rounded-3 overflow-hidden" style="max-height: 550px; min-height: 400px;">
                                        <img id="imagePreview" src="#" alt="Preview" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        <div class="position-absolute bottom-0 start-0 w-100 p-3 bg-dark bg-opacity-75 text-white d-flex justify-content-between align-items-center">
                                            <span class="small text-truncate" id="fileNameDisplay">Nama File...</span>
                                            <button type="button" class="btn btn-danger rounded-circle p-2 shadow" id="removeFile" title="Hapus Foto">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Kolom Kanan: Tombol Konfirmasi --}}
                            <div class="col-lg-4 bg-light d-flex flex-column justify-content-center p-5 position-relative">

                                {{-- OVERLAY LOADING --}}
                                <div id="processOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-95 d-flex flex-column justify-content-center align-items-center d-none" style="z-index: 10; border-left: 1px solid #dee2e6;">
                                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                                    <h5 class="fw-bold text-dark mb-1" id="loadingTitle">Memproses...</h5>
                                    <p class="text-muted small text-center px-4" id="loadingText">Mohon tunggu sebentar</p>
                                </div>

                                <h4 class="fw-bold mb-4 text-center">Konfirmasi Absensi</h4>
                                <div class="d-grid gap-4">
                                    @if(is_null($absensiMasuk))
                                        {{-- Tombol Absen Masuk --}}
                                        <button type="button" onclick="startAbsensi('masuk')" class="btn btn-primary btn-lg py-4 fs-4 shadow-sm">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="bi bi-box-arrow-in-right me-3 fs-2"></i> <span>Absen Masuk</span>
                                            </div>
                                        </button>
                                        {{-- Tombol Pulang Disabled --}}
                                        <button type="button" class="btn btn-secondary btn-lg py-3 disabled" style="opacity: 0.6"><i class="bi bi-lock-fill me-2"></i> Absen Pulang Terkunci</button>
                                    @else
                                        {{-- Tombol Masuk Disabled (Sudah) --}}
                                        <button type="button" class="btn btn-success btn-lg py-3 disabled" style="opacity: 1"><i class="bi bi-check-circle-fill me-2"></i> Sudah Masuk</button>
                                        
                                        @if(is_null($absensiPulang))
                                            {{-- Tombol Absen Pulang --}}
                                            <button type="button" onclick="startAbsensi('pulang')" class="btn btn-danger btn-lg py-4 fs-4 shadow-sm mt-3">
                                                <div class="d-flex align-items-center justify-content-center"><i class="bi bi-box-arrow-right me-3 fs-2"></i><span>Absen Pulang</span></div>
                                            </button>
                                        @else
                                            {{-- Tombol Pulang Disabled (Sudah) --}}
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
    .btn-lg { border-radius: 12px; }
    .dark-mode .upload-area, .dark-mode .col-lg-4.bg-light { background-color: #2b2b2b !important; border-color: #444 !important; }
    .dark-mode .bg-body-secondary { background-color: #3a3a3a !important; }
    .dark-mode .border-end { border-right-color: #444 !important; }
    .dark-mode #processOverlay { background-color: rgba(43, 43, 43, 0.95) !important; border-left-color: #444 !important; }
    .dark-mode .text-dark { color: #fff !important; }
    .dark-mode .text-muted { color: #aaa !important; }
    .border-dashed { border-style: dashed !important; border-color: #dee2e6; transition: all 0.3s ease; }
    .dark-mode .border-dashed { border-color: #555; }
    .upload-area:hover, .upload-area.dragover { background-color: #e9ecef !important; border-color: var(--primary-color) !important; }
    .dark-mode .upload-area:hover, .dark-mode .upload-area.dragover { background-color: #333 !important; }
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
        const fileNameDisplay = document.getElementById('fileNameDisplay');

        const workAreaEl = document.getElementById('work-area-data');
        const OFFICE_LAT = workAreaEl ? parseFloat(workAreaEl.dataset.lat) : 0;
        const OFFICE_LNG = workAreaEl ? parseFloat(workAreaEl.dataset.lng) : 0;
        const OFFICE_RAD = workAreaEl ? parseFloat(workAreaEl.dataset.rad) : 50;

        function handleFile(file) {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    if(fileNameDisplay) fileNameDisplay.textContent = file.name;
                    uploadPlaceholder.classList.add('d-none');
                    previewContainer.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        }
        fileInput.addEventListener('change', function(e) { handleFile(e.target.files[0]); });

        dropZone.addEventListener('click', (e) => {
            if(e.target !== removeFileBtn && !removeFileBtn.contains(e.target)) fileInput.click();
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); }, false));
        ['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.add('dragover'), false));
        ['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.remove('dragover'), false));

        dropZone.addEventListener('drop', (e) => {
            fileInput.files = e.dataTransfer.files;
            handleFile(e.dataTransfer.files[0]);
        });

        removeFileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.value = '';
            imagePreview.src = '#';
            previewContainer.classList.add('d-none');
            uploadPlaceholder.classList.remove('d-none');
        });

        window.startAbsensi = function(type) {
            const form = document.getElementById('uploadForm');
            const overlay = document.getElementById('processOverlay');
            const loadingTitle = document.getElementById('loadingTitle');
            const loadingText = document.getElementById('loadingText');
            const inputType = document.getElementById('attendanceType');

            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Mohon pilih atau ambil foto bukti absensi terlebih dahulu!');
                return;
            }

            inputType.value = type;
            overlay.classList.remove('d-none');
            loadingTitle.innerText = "Cek Foto & Lokasi";
            loadingText.innerText = "Sedang memvalidasi data...";

            const formData = new FormData();
            formData.append('foto_absensi', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("karyawan.absensi.checkExif") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'error') {
                    throw new Error(data.message);
                }

                loadingTitle.innerText = "Mengambil GPS...";
                return new Promise((resolve, reject) => {
                    if (!navigator.geolocation) {
                        reject(new Error("Browser Anda tidak mendukung Geolocation."));
                    } else {
                        navigator.geolocation.getCurrentPosition(
                            (position) => { resolve(position); },
                            (error) => {
                                let msg = "Gagal mengambil lokasi browser.";
                                if (error.code == error.PERMISSION_DENIED) msg = "Izin lokasi ditolak. Wajib aktifkan lokasi.";
                                reject(new Error(msg));
                            },
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                        );
                    }
                });
            })
            .then((position) => {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;

                document.getElementById('browser_lat').value = userLat;
                document.getElementById('browser_lng').value = userLng;

                const distance = haversineDistance(userLat, userLng, OFFICE_LAT, OFFICE_LNG);
                console.log(`Jarak User: ${distance}m`);

                if (distance <= OFFICE_RAD) {
                    loadingTitle.innerText = "Mengirim Data...";
                    loadingText.innerText = "Verifikasi berhasil. Menyimpan absensi...";
                    form.submit();
                } else {
                    overlay.classList.add('d-none');
                    // Alert yang lebih singkat
                    const selisih = Math.round(distance - OFFICE_RAD);
                    alert(`GAGAL: Anda berada ${selisih} m di luar radius kantor.`);
                }
            })
            .catch(error => {
                overlay.classList.add('d-none');
                alert("ABSENSI GAGAL:\n" + error.message);
            });
        };

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
    });
</script>
@endpush