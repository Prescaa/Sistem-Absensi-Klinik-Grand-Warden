@extends('layouts.manajemen_app')

@section('page-title', 'Validasi & Approval')

@section('content')
<div class="row">
    <div class="col-12">

        <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="pills-absensi-tab" data-bs-toggle="pill" data-bs-target="#pills-absensi" type="button">
                    <i class="bi bi-camera-fill me-2"></i>Validasi Absensi
                    @if($pendingAbsensi->count() > 0) <span class="badge bg-danger ms-2">{{ $pendingAbsensi->count() }}</span> @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="pills-izin-tab" data-bs-toggle="pill" data-bs-target="#pills-izin" type="button">
                    <i class="bi bi-envelope-paper-fill me-2"></i>Approval Izin
                    @if($pendingIzin->count() > 0) <span class="badge bg-danger ms-2">{{ $pendingIzin->count() }}</span> @endif
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">

            {{-- TAB ABSENSI (GRID CARD LAYOUT) --}}
            <div class="tab-pane fade show active" id="pills-absensi">
                
                @if($pendingAbsensi->count() > 0)
                    <div class="row g-4">
                        @foreach($pendingAbsensi as $att)
                            @php
                                $isOwnData = (Auth::user()->employee->emp_id == $att->emp_id);
                                
                                // Logika Sederhana Keterlambatan (Ambil dari workArea relation, default 08:00)
                                $jamMasukBatas = $att->workArea->jam_kerja['masuk'] ?? '08:00';
                                if(strlen($jamMasukBatas) == 5) $jamMasukBatas .= ':00'; // Ensure H:i:s

                                $isLate = $att->type == 'masuk' && $att->waktu_unggah->format('H:i:s') > $jamMasukBatas;
                            @endphp
                            
                            <div class="col-md-6 col-xl-4">
                                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                                    
                                    {{-- 1. FOTO BESAR DI ATAS --}}
                                    <div class="position-relative" style="height: 220px; background-color: #f0f0f0;">
                                        <a href="{{ asset($att->nama_file_foto) }}" target="_blank">
                                            <img src="{{ asset($att->nama_file_foto) }}" class="w-100 h-100 object-fit-cover" alt="Bukti Absensi">
                                        </a>
                                        
                                        {{-- Badge Tipe di Pojok Kanan Atas Foto --}}
                                        <span class="position-absolute top-0 end-0 m-3 badge {{ $att->type == 'masuk' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 shadow-sm rounded-pill fw-bold">
                                            {{ ucfirst($att->type) }}
                                        </span>
                                    </div>

                                    <div class="card-body p-4 d-flex flex-column">
                                        
                                        {{-- 2. INFORMASI KARYAWAN --}}
                                        <div class="mb-3">
                                            <h5 class="fw-bold text-dark-emphasis mb-0">{{ $att->employee->nama }}</h5>
                                            <small class="text-muted">{{ $att->employee->nip }}</small>
                                            
                                            @if($isOwnData)
                                                <div class="mt-1"><span class="badge bg-danger">Milik Anda</span></div>
                                            @endif
                                        </div>

                                        {{-- 3. INFORMASI WAKTU & LOKASI (BOX ABU) --}}
                                        <div class="bg-light p-3 rounded border mb-3 small">
                                            {{-- Baris Waktu & Status --}}
                                            <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-calendar-event text-primary me-2 fs-6"></i>
                                                    <span class="text-dark fw-bold">{{ $att->waktu_unggah->format('d M Y, H:i') }}</span>
                                                </div>
                                                
                                                {{-- Status Terlambat/Tepat Waktu --}}
                                                @if($att->type == 'masuk')
                                                    @if($isLate)
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">Terlambat (> {{ substr($jamMasukBatas, 0, 5) }})</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">Tepat Waktu</span>
                                                    @endif
                                                @endif
                                            </div>

                                            {{-- Baris Lokasi & Link Map --}}
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center text-truncate me-2" style="max-width: 60%;">
                                                    <i class="bi bi-geo-alt text-danger me-2 fs-6"></i>
                                                    {{-- Koordinat jadi Link --}}
                                                    <a href="https://maps.google.com/?q={{ $att->latitude }},{{ $att->longitude }}" 
                                                       target="_blank" 
                                                       class="text-decoration-none text-dark fw-bold text-truncate"
                                                       title="Klik untuk lihat di Google Maps">
                                                        {{ $att->latitude }}, {{ $att->longitude }}
                                                    </a>
                                                </div>
                                                <a href="https://maps.google.com/?q={{ $att->latitude }},{{ $att->longitude }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 shadow-sm" style="font-size: 0.75rem;">
                                                    <i class="bi bi-map-fill me-1"></i> Cek Map
                                                </a>
                                            </div>
                                        </div>

                                        {{-- 4. FORM VALIDASI (INPUT & BUTTONS) --}}
                                        @if(!$isOwnData)
                                            <form action="{{ route('manajemen.validasi.submit') }}" method="POST" class="mt-auto">
                                                @csrf
                                                <input type="hidden" name="att_id" value="{{ $att->att_id }}">

                                                {{-- Textarea Catatan --}}
                                                <div class="mb-3">
                                                    <textarea name="catatan_validasi" class="form-control form-control-sm" 
                                                              rows="2" placeholder="Catatan validasi (opsional). Hanya huruf, angka, spasi, titik, koma, atau strip yang diizinkan."
                                                              oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-]/g, '')"></textarea>
                                                </div>

                                                {{-- Tombol Aksi (Grid 2 Kolom) --}}
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <button type="submit" name="status_validasi" value="Valid" class="btn btn-success w-100 fw-bold text-white py-2">
                                                            <i class="bi bi-check-lg me-1"></i> Terima
                                                        </button>
                                                    </div>
                                                    <div class="col-6">
                                                        <button type="submit" name="status_validasi" value="Invalid" class="btn btn-danger w-100 fw-bold text-white py-2" onclick="return confirm('Yakin ingin menolak absensi ini?')">
                                                            <i class="bi bi-x-lg me-1"></i> Tolak
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        @else
                                            <div class="alert alert-secondary text-center small mt-auto mb-0">
                                                <i class="bi bi-info-circle me-1"></i> Menunggu validasi manajer lain.
                                            </div>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5 text-muted bg-white rounded shadow-sm">
                        <i class="bi bi-check-circle display-1 text-success d-block mb-3"></i>
                        <h5 class="fw-bold">Semua Bersih!</h5>
                        <p class="mb-0">Tidak ada antrean absensi yang perlu divalidasi saat ini.</p>
                    </div>
                @endif

            </div>

            {{-- TAB IZIN (Tabel) --}}
            <div class="tab-pane fade" id="pills-izin">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal Pengajuan</th>
                                        <th>Karyawan</th>
                                        <th>Jenis</th>
                                        <th>Bukti</th>
                                        <th>Ket.</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingIzin as $leave)
                                    @php
                                        $isOwnData = (Auth::user()->employee->emp_id == $leave->emp_id);
                                    @endphp
                                    <tr class="{{ $isOwnData ? 'table-warning-soft' : '' }}">
                                        <td>{{ $leave->created_at->format('d M Y') }}</td>
                                        <td>
                                            <div class="fw-bold text-dark-emphasis">{{ $leave->employee->nama }}</div>
                                            <div class="small text-muted">{{ $leave->employee->nip }}</div>
                                            @if($isOwnData) 
                                                <span class="badge bg-danger mt-1" style="font-size: 0.65rem;">Milik Anda</span> 
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $leave->tipe_izin == 'sakit' ? 'bg-danger' : ($leave->tipe_izin == 'cuti' ? 'bg-info' : 'bg-warning text-dark') }}">{{ ucfirst($leave->tipe_izin) }}</span>
                                        </td>
                                        <td>
                                            @if($leave->file_bukti)
                                                <a href="{{ asset($leave->file_bukti) }}" target="_blank" class="btn btn-sm btn-light border text-primary fw-bold py-0 px-2 shadow-sm" title="Klik untuk lihat bukti">
                                                    <i class="bi bi-image me-1"></i> Lihat Bukti
                                                </a>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-truncate" style="max-width: 150px;">{{ $leave->deskripsi }}</td>
                                        <td>
                                            @if($isOwnData)
                                                <span class="badge bg-secondary fst-italic"><i class="bi bi-hourglass-split me-1"></i> Menunggu Approval</span>
                                            @else
                                                {{-- PERBAIKAN UI TOMBOL PROSES --}}
                                                <button class="btn btn-primary btn-sm px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#izin{{ $leave->leave_id }}">
                                                    <i class="bi bi-pencil-square me-1"></i> Proses
                                                </button>

                                                <div class="modal fade" id="izin{{ $leave->leave_id }}">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <form action="{{ route('manajemen.validasi.izin.submit') }}" method="POST">
                                                            @csrf
                                                            <div class="modal-content">
                                                                <div class="modal-header border-0 pb-0"><h6 class="modal-title fw-bold">Approval Izin: {{ $leave->employee->nama }}</h6></div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="leave_id" value="{{ $leave->leave_id }}">
                                                                    <div class="mb-3 bg-light p-3 rounded border small">
                                                                        <strong>Alasan:</strong> {{ $leave->deskripsi }}
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold">Keputusan</label>
                                                                        <select name="status" class="form-select" required>
                                                                            <option value="disetujui">Setujui</option>
                                                                            <option value="ditolak">Tolak</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold">Catatan Admin</label>
                                                                        <textarea name="catatan_admin" class="form-control" rows="2"
                                                                                  oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-]/g, '')"></textarea>
                                                                        <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, dan strip (-) yang diizinkan.</div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-0 bg-light">
                                                                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted py-5">Tidak ada pengajuan izin.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-warning-soft {
        background-color: #fff3cd !important;
    }
    
    /* === DARK MODE (VALIDASI) === */
    
    /* 1. Card & Global */
    .dark-mode .card {
        background-color: #1e1e1e !important;
        border: 1px solid #444 !important;
        color: #e0e0e0;
    }
    
    .dark-mode .card-body {
        background-color: #1e1e1e !important;
    }
    
    /* 2. Nav Pills Container */
    .dark-mode .nav-pills.bg-white {
        background-color: #2d2d2d !important;
        border: 1px solid #444 !important;
    }
    
    .dark-mode .nav-link {
        color: #adb5bd !important;
        background-color: transparent !important;
    }
    
    .dark-mode .nav-link.active {
        background-color: #0d6efd !important;
        color: #fff !important;
        border: 1px solid #0d6efd !important;
    }
    
    .dark-mode .nav-link:hover:not(.active) {
        background-color: #3d3d3d !important;
        color: #fff !important;
    }
    
    /* 3. Badge Count */
    .dark-mode .badge.bg-danger {
        background-color: #dc3545 !important;
        color: #fff !important;
    }
    
    /* 4. Tabel */
    .dark-mode .table {
        border-color: #444 !important;
        color: #e0e0e0 !important;
    }
    
    .dark-mode .table-light th {
        background-color: #2d2d2d !important;
        border-color: #444 !important;
        color: #fff !important;
    }
    
    .dark-mode .table tbody td {
        background-color: #1e1e1e !important;
        border-bottom: 1px solid #444 !important;
        color: #e0e0e0 !important;
    }
    
    .dark-mode .table-hover tbody tr:hover td {
        background-color: #2d2d2d !important;
    }
    
    /* 5. Highlight Row "Milik Anda" (Dark Mode) */
    .dark-mode .table-warning-soft td {
        background-color: #332701 !important;
        color: #ffda6a !important;
        border-bottom: 1px solid #554200 !important;
    }
    
    /* 6. Text Colors */
    .dark-mode .text-dark-emphasis { 
        color: #fff !important; 
    }
    
    .dark-mode .text-muted { 
        color: #adb5bd !important; 
    }
    
    /* 7. Badges in Table */
    .dark-mode .badge.bg-success {
        background-color: #198754 !important;
    }
    
    .dark-mode .badge.bg-warning {
        background-color: #ffc107 !important;
        color: #000 !important;
    }
    
    .dark-mode .badge.bg-secondary {
        background-color: #6c757d !important;
    }
    
    .dark-mode .badge.bg-danger {
        background-color: #dc3545 !important;
    }
    
    /* 8. Buttons */
    .dark-mode .btn-outline-info {
        color: #0dcaf0 !important;
        border-color: #0dcaf0 !important;
    }
    
    .dark-mode .btn-outline-info:hover {
        background-color: #0dcaf0 !important;
        color: #000 !important;
    }
    
    .dark-mode .btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    
    .dark-mode .btn-success {
        background-color: #198754 !important;
        border-color: #198754 !important;
    }
    
    .dark-mode .btn-primary {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    
    /* 9. Modal */
    .dark-mode .modal-content {
        background-color: #1e1e1e !important;
        border: 1px solid #444 !important;
        color: #fff !important;
    }
    
    .dark-mode .modal-header {
        border-bottom: 1px solid #444 !important;
    }
    
    .dark-mode .modal-footer, 
    .dark-mode .bg-light {
        background-color: #2d2d2d !important;
        border-top: 1px solid #444 !important;
    }
    
    .dark-mode .form-control, 
    .dark-mode .form-select {
        background-color: #2d2d2d !important;
        border-color: #555 !important;
        color: #fff !important;
    }
    
    .dark-mode .form-control:focus, 
    .dark-mode .form-select:focus {
        border-color: #0d6efd !important;
        background-color: #2d2d2d !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        color: #fff !important;
    }
    
    .dark-mode .form-control::placeholder {
        color: #adb5bd !important;
    }
    
    .dark-mode .btn-close { 
        filter: invert(1) grayscale(100%) brightness(200%); 
    }
    
    /* 10. Image Border in Dark Mode */
    .dark-mode img.rounded.border {
        border: 1px solid #555 !important;
    }
    
    /* 11. Link in Dark Mode */
    .dark-mode a.text-dark {
        color: #6ea8fe !important;
    }
    .dark-mode a.text-dark:hover {
        color: #8bb9fe !important;
    }
    
    /* 12. Validasi Absensi Box Light */
    .dark-mode .bg-light.p-3.rounded.border {
        background-color: #2b2b2b !important;
        border-color: #444 !important;
    }
    
    /* 13. Map Link in Dark Mode */
    .dark-mode a.text-dark {
        color: #6ea8fe !important;
    }
    .dark-mode a.text-dark:hover {
        color: #8bb9fe !important;
    }
    
    /* 14. Badge subtles for Dark Mode */
    .dark-mode .badge.bg-danger-subtle {
        background-color: rgba(220, 53, 69, 0.2) !important;
        color: #ea868f !important;
        border-color: #842029 !important;
    }
    .dark-mode .badge.bg-success-subtle {
        background-color: rgba(25, 135, 84, 0.2) !important;
        color: #75b798 !important;
        border-color: #0f5132 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash; 
        if (hash) {
            const triggerEl = document.querySelector(`button[data-bs-target="${hash}"]`);
            if (triggerEl) triggerEl.click();
        }
    });
</script>
@endpush