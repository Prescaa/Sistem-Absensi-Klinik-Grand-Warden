@extends('layouts.app')

@section('page-title', 'Unggah Foto Absensi')

@section('content')
<div class="container-fluid">
    {{-- Data Work Area untuk JS (Dikirim dari Controller) --}}
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
                            <div class="alert alert-warning border-0 d-inline-flex align-items-center px-4 py-2">
                                <i class="bi bi-phone-vibrate fs-4 me-3"></i>
                                <div class="text-start">
                                    <strong>PENTING:</strong><br>
                                    Sistem akan memverifikasi Lokasi Foto (EXIF) dan Lokasi HP (GPS) secara bersamaan.
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
                        {{-- Hidden Input untuk menyimpan Tipe Absen (Masuk/Pulang) --}}
                        <input type="hidden" name="type" id="attendanceType">

                        {{-- === SECURITY UPDATE: Input untuk Lokasi Browser === --}}
                        <input type="hidden" name="browser_lat" id="browser_lat">
                        <input type="hidden" name="browser_lng" id="browser_lng">
                        {{-- ================================================== --}}

                        <div class="row g-0">
                            {{-- Kolom Kiri: Upload Area --}}
                            <div class="col-lg-8 border-end">
                                <div class="upload-area d-flex flex-column justify-content-center align-items-center text-center p-5" id="dropZone" style="min-height: 600px; cursor: pointer; background-color: #fcfcfc;">

                                    <input type="file" id="foto_absensi" name="foto_absensi" class="d-none" accept="image/jpeg,image/png" required>

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

                                {{-- OVERLAY LOADING (Hidden by default) --}}
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
                                        <button type="button" class="btn btn-secondary btn-lg py-3 disabled" style="opacity: 0.6"><i class="bi bi-lock-fill me-2"></i> Absen Pulang Terkunci</button>
                                    @else
                                        <button type="button" class="btn btn-success btn-lg py-3 disabled" style="opacity: 1"><i class="bi bi-check-circle-fill me-2"></i> Sudah Masuk</button>
                                        @if(is_null($absensiPulang))
                                            {{-- Tombol Absen Pulang --}}
                                            <button type="button" onclick="startAbsensi('pulang')" class="btn btn-danger btn-lg py-4 fs-4 shadow-sm mt-3">
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
        // --- SETUP UI VARIABLES ---
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('foto_absensi');
        const previewContainer = document.getElementById('previewContainer');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        const imagePreview = document.getElementById('imagePreview');
        const removeFileBtn = document.getElementById('removeFile');
        const fileNameDisplay = document.getElementById('fileNameDisplay');

        // Data WorkArea dari Backend
        const workAreaEl = document.getElementById('work-area-data');
        const OFFICE_LAT = workAreaEl ? parseFloat(workAreaEl.dataset.lat) : 0;
        const OFFICE_LNG = workAreaEl ? parseFloat(workAreaEl.dataset.lng) : 0;
        const OFFICE_RAD = workAreaEl ? parseFloat(workAreaEl.dataset.rad) : 50;

        // --- FILE HANDLING ---
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

        // ==========================================
        // --- LOGIKA UTAMA (Double Validation) ---
        // ==========================================
        window.startAbsensi = function(type) {
            const form = document.getElementById('uploadForm');
            const overlay = document.getElementById('processOverlay');
            const loadingTitle = document.getElementById('loadingTitle');
            const loadingText = document.getElementById('loadingText');
            const inputType = document.getElementById('attendanceType');

            // 1. Validasi File Ada
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Mohon pilih atau ambil foto bukti absensi terlebih dahulu!');
                return;
            }

            // 2. Set Hidden Input Type
            inputType.value = type;

            // 3. Tampilkan Overlay Loading
            overlay.classList.remove('d-none');
            loadingTitle.innerText = "Cek Foto & Lokasi";
            loadingText.innerText = "Sedang memvalidasi data...";

            // --- LAYER 1: AJAX Check EXIF (Opsional, untuk Quick Fail) ---
            // Kita tetap cek EXIF di frontend supaya user tahu lebih cepat kalau fotonya tidak valid
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

                // --- LAYER 2: Browser Geolocation ---
                loadingTitle.innerText = "Mengambil GPS...";
                return new Promise((resolve, reject) => {
                    if (!navigator.geolocation) {
                        reject(new Error("Browser Anda tidak mendukung Geolocation."));
                    } else {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                resolve(position);
                            },
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
                // Data GPS Browser didapatkan
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;

                // 1. MASUKKAN KE FORM (Hidden Inputs)
                // Ini bagian terpenting agar Controller bisa membaca lokasi browser
                document.getElementById('browser_lat').value = userLat;
                document.getElementById('browser_lng').value = userLng;

                // 2. Cek Jarak (Client-Side Check - UX Only)
                // Meskipun backend akan cek ulang, kita kasih info di depan biar user tidak bingung kalau gagal
                const distance = haversineDistance(userLat, userLng, OFFICE_LAT, OFFICE_LNG);
                console.log(`Jarak User: ${distance}m`);

                if (distance <= OFFICE_RAD) {
                    // SUKSES: Kirim Form
                    loadingTitle.innerText = "Mengirim Data...";
                    loadingText.innerText = "Verifikasi berhasil. Menyimpan absensi...";
                    form.submit();
                } else {
                    // GAGAL: Jarak Jauh
                    overlay.classList.add('d-none');
                    alert(`GAGAL: Posisi Anda terdeteksi sejauh ${Math.round(distance)}m dari kantor. Maksimal radius: ${OFFICE_RAD}m.`);
                }
            })
            .catch(error => {
                // Tangkap Error (Baik dari EXIF maupun GPS)
                overlay.classList.add('d-none');
                alert("ABSENSI GAGAL:\n" + error.message);
            });
        };

        // Rumus Haversine (Meter)
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
