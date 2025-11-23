@extends('layouts.admin_app')

@section('page-title', 'Validasi & Persetujuan')

@section('content')
<div class="container-fluid">

    {{-- Notifikasi Sukses/Error --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- TAB NAVIGATION --}}
    <ul class="nav nav-tabs mb-4" id="validasiTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="absensi-tab" data-bs-toggle="tab" data-bs-target="#absensi" type="button" role="tab">
                <i class="bi bi-camera-fill me-2"></i>Validasi Foto Absensi
                @if(isset($attendances) && $attendances->count() > 0)
                    <span class="badge bg-danger ms-2">{{ $attendances->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="izin-tab" data-bs-toggle="tab" data-bs-target="#izin" type="button" role="tab">
                <i class="bi bi-file-medical-fill me-2"></i>Persetujuan Izin/Sakit
                @if(isset($leaves) && $leaves->count() > 0)
                    <span class="badge bg-danger ms-2">{{ $leaves->count() }}</span>
                @endif
            </button>
        </li>
    </ul>

    {{-- TAB CONTENT --}}
    <div class="tab-content" id="validasiTabContent">

        {{-- === TAB 1: VALIDASI ABSENSI === --}}
        <div class="tab-pane fade show active" id="absensi" role="tabpanel">
            @if($attendances->isEmpty())
                <div class="alert alert-secondary border-0 bg-light text-center py-5">
                    <i class="bi bi-check-all display-1 text-muted mb-3"></i><br>
                    <h5 class="text-muted">Tidak ada data absensi yang perlu divalidasi.</h5>
                </div>
            @else
                <div class="row">
                    @foreach($attendances as $att)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm h-100 border-0">
                            <div class="position-relative">
                                {{-- Link ke Foto --}}
                                <a href="{{ $att->nama_file_foto }}" target="_blank">
                                    <img src="{{ $att->nama_file_foto }}" class="card-img-top" alt="Foto Absensi" style="height: 250px; object-fit: cover;">
                                </a>
                                <span class="position-absolute top-0 end-0 badge bg-dark m-2 shadow-sm">{{ ucfirst($att->type) }}</span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold mb-1">{{ $att->employee->nama ?? 'Karyawan' }}</h5>
                                <small class="text-muted d-block mb-3">{{ $att->employee->nip ?? '-' }}</small>
                                
                                <ul class="list-unstyled text-muted small mb-3 bg-light p-3 rounded">
                                    <li class="mb-2"><i class="bi bi-calendar-event me-2 text-primary"></i> {{ $att->waktu_unggah->format('d M Y, H:i:s') }}</li>
                                    
                                    <li class="d-flex align-items-start">
                                        <i class="bi bi-geo-alt me-2 text-danger mt-1"></i>
                                        <div class="w-100">
                                            {{-- 
                                                LOGIKA VALIDASI KOORDINAT 0:
                                                Kita cek nilai absolut latitude & longitude. 
                                                Jika keduanya sangat kecil (< 0.0001), kita anggap 0 (invalid).
                                            --}}
                                            @if(abs($att->latitude) < 0.0001 && abs($att->longitude) < 0.0001)
                                                <div class="text-danger fw-bold small mb-1">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Lokasi GPS Hilang
                                                </div>
                                                <div class="small text-muted fst-italic" style="font-size: 0.75rem; line-height: 1.2;">
                                                    Metadata lokasi dihapus oleh browser HP atau GPS mati saat foto diambil.
                                                </div>
                                            @else
                                                {{-- Koordinat Valid --}}
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ $att->latitude }},{{ $att->longitude }}" target="_blank" class="fw-bold text-decoration-none text-dark hover-primary" title="Buka di Google Maps">
                                                    {{ number_format($att->latitude, 5) }}, {{ number_format($att->longitude, 5) }}
                                                    <i class="bi bi-box-arrow-up-right ms-1 small text-primary"></i>
                                                </a>
                                                
                                                {{-- Container Alamat (Diisi otomatis oleh JS) --}}
                                                <div class="small text-muted fst-italic mt-1 location-address" 
                                                     data-lat="{{ $att->latitude }}" 
                                                     data-lng="{{ $att->longitude }}"
                                                     style="line-height: 1.2;">
                                                    <span class="spinner-border spinner-border-sm text-secondary" style="width: 0.7rem; height: 0.7rem; border-width: 1px;" role="status"></span>
                                                    <span class="ms-1">Memuat alamat...</span>
                                                </div>
                                            @endif
                                        </div>
                                    </li>
                                </ul>

                                <form action="{{ route('admin.validasi.submit') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="att_id" value="{{ $att->att_id }}">

                                    <div class="mb-3">
                                        <textarea name="catatan_validasi" class="form-control form-control-sm" rows="2" placeholder="Catatan admin (opsional)..."></textarea>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" name="status_validasi" value="Valid" class="btn btn-success flex-fill btn-sm fw-bold py-2">
                                            <i class="bi bi-check-lg"></i> Terima
                                        </button>

                                        <button type="submit" name="status_validasi" value="Invalid" class="btn btn-danger flex-fill btn-sm fw-bold py-2">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- === TAB 2: VALIDASI IZIN === --}}
        <div class="tab-pane fade" id="izin" role="tabpanel">
            @if($leaves->isEmpty())
                <div class="alert alert-secondary border-0 bg-light text-center py-5">
                    <i class="bi bi-emoji-smile display-1 text-muted mb-3"></i><br>
                    <h5 class="text-muted">Tidak ada pengajuan izin baru.</h5>
                </div>
            @else
                <div class="row">
                    @foreach($leaves as $leave)
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <h6 class="fw-bold mb-0">{{ $leave->employee->nama ?? 'Nama Tidak Ditemukan' }}</h6>
                                    <small class="text-muted">{{ $leave->employee->nip ?? '-' }}</small>
                                </div>
                                @if($leave->tipe_izin == 'sakit')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Sakit</span>
                                @elseif($leave->tipe_izin == 'cuti')
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">Cuti</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Izin</span>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6 border-end">
                                        <small class="text-muted d-block">Tanggal Mulai</small>
                                        <strong class="text-dark">{{ \Carbon\Carbon::parse($leave->tanggal_mulai)->format('d M Y') }}</strong>
                                    </div>
                                    <div class="col-md-6 ps-4">
                                        <small class="text-muted d-block">Tanggal Selesai</small>
                                        <strong class="text-dark">{{ \Carbon\Carbon::parse($leave->tanggal_selesai)->format('d M Y') }}</strong>
                                    </div>
                                </div>

                                <div class="mb-3 bg-light p-3 rounded border">
                                    <small class="text-muted d-block mb-1 fw-bold"><i class="bi bi-text-left me-1"></i>Alasan:</small>
                                    <p class="mb-0 fst-italic text-dark">"{{ $leave->deskripsi }}"</p>
                                </div>

                                @if($leave->file_bukti)
                                    <div class="mb-3">
                                        <a href="{{ asset($leave->file_bukti) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="bi bi-paperclip me-2"></i>Lihat Bukti Dokumen
                                        </a>
                                    </div>
                                @endif

                                <hr>

                                <form action="{{ route('admin.validasi.izin.submit') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="leave_id" value="{{ $leave->leave_id }}">

                                    <div class="mb-3">
                                        <input type="text" name="catatan_admin" class="form-control form-control-sm" placeholder="Catatan persetujuan/penolakan...">
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" name="status" value="disetujui" class="btn btn-success flex-fill fw-bold">
                                            <i class="bi bi-check-circle me-2"></i>Setujui
                                        </button>
                                        <button type="submit" name="status" value="ditolak" class="btn btn-danger flex-fill fw-bold">
                                            <i class="bi bi-x-circle me-2"></i>Tolak
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const delay = ms => new Promise(res => setTimeout(res, ms));
        const addressElements = document.querySelectorAll('.location-address');

        async function fetchAddresses() {
            for (let i = 0; i < addressElements.length; i++) {
                const el = addressElements[i];
                const lat = parseFloat(el.getAttribute('data-lat'));
                const lng = parseFloat(el.getAttribute('data-lng'));

                // Validasi Ekstra di JS: Jika 0 atau NaN, jangan fetch ke API Nominatim
                if (!lat || !lng || isNaN(lat) || isNaN(lng) || (Math.abs(lat) < 0.0001 && Math.abs(lng) < 0.0001)) {
                    el.innerHTML = '<span class="text-muted small">- Data lokasi kosong -</span>';
                    continue;
                }

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                        headers: { 'User-Agent': 'KlinikGrandWardenApp/1.0' }
                    });

                    if (!response.ok) throw new Error('Network response was not ok');

                    const data = await response.json();
                    
                    if (data && data.display_name) {
                        el.innerHTML = `<i class="bi bi-pin-map-fill text-secondary me-1"></i> ${data.display_name}`;
                    } else {
                        throw new Error('Alamat tidak ditemukan');
                    }

                } catch (error) {
                    console.error("Gagal memuat alamat:", error);
                    el.innerHTML = '<span class="text-muted text-decoration-underline" style="cursor:help" title="Server peta tidak merespon.">Gagal memuat alamat</span>';
                }

                await delay(1200); // Rate limit
            }
        }

        if (addressElements.length > 0) {
            fetchAddresses();
        }
    });
</script>
@endpush