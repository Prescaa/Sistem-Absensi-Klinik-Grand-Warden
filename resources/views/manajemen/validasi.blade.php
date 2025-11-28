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

            {{-- TAB ABSENSI --}}
            <div class="tab-pane fade show active" id="pills-absensi">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Karyawan</th>
                                        <th>Tipe</th>
                                        <th>Foto</th>
                                        <th>Lokasi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingAbsensi as $att)
                                    @php
                                        $isOwnData = (Auth::user()->employee->emp_id == $att->emp_id);
                                    @endphp
                                    <tr class="{{ $isOwnData ? 'table-warning-soft' : '' }}">
                                        <td>{{ $att->waktu_unggah->format('d M H:i') }}</td>
                                        <td>
                                            <div class="fw-bold text-dark-emphasis">{{ $att->employee->nama }}</div>
                                            <div class="small text-muted">{{ $att->employee->nip }}</div>
                                            @if($isOwnData) 
                                                <span class="badge bg-danger mt-1" style="font-size: 0.65rem;">Milik Anda</span> 
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $att->type == 'masuk' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($att->type) }}</span></td>
                                        <td>
                                            <img src="{{ asset($att->nama_file_foto) }}" class="rounded border" width="50" height="50" style="cursor:pointer; object-fit:cover;" data-bs-toggle="modal" data-bs-target="#img{{ $att->att_id }}">
                                            {{-- Modal Preview --}}
                                            <div class="modal fade" id="img{{ $att->att_id }}">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-body p-0">
                                                            <img src="{{ asset($att->nama_file_foto) }}" class="w-100 rounded">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="https://maps.google.com/?q={{ $att->latitude }},{{ $att->longitude }}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-geo-alt me-1"></i>Map</a>
                                        </td>
                                        <td>
                                            @if($isOwnData)
                                                <span class="badge bg-secondary fst-italic py-2 px-3"><i class="bi bi-hourglass-split me-1"></i> Menunggu Manajer Lain</span>
                                            @else
                                                <div class="d-flex gap-2">
                                                    <form action="{{ route('manajemen.validasi.submit') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="att_id" value="{{ $att->att_id }}">
                                                        <input type="hidden" name="status_validasi" value="Invalid">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tolak absensi ini?')"><i class="bi bi-x-lg"></i></button>
                                                    </form>
                                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#acc{{ $att->att_id }}"><i class="bi bi-check-lg"></i></button>
                                                </div>

                                                {{-- Modal Validasi --}}
                                                <div class="modal fade" id="acc{{ $att->att_id }}">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <form action="{{ route('manajemen.validasi.submit') }}" method="POST">
                                                            @csrf
                                                            <div class="modal-content">
                                                                <div class="modal-header border-0 pb-0"><h6 class="modal-title fw-bold">Validasi: {{ $att->employee->nama }}</h6></div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="att_id" value="{{ $att->att_id }}">
                                                                    <input type="hidden" name="status_validasi" value="Valid">
                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold">Catatan (Opsional)</label>
                                                                        <input type="text" name="catatan_validasi" class="form-control" placeholder="Contoh: OK, Tepat Waktu"
                                                                               oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-\/]/g, '')">
                                                                        <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, strip, dan garis miring.</div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-0 bg-light">
                                                                    <button type="button" class="btn btn-link text-decoration-none text-muted" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="btn btn-success px-4 fw-bold">Setujui</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted py-5">Tidak ada antrean absensi saat ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB IZIN --}}
            <div class="tab-pane fade" id="pills-izin">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Karyawan</th>
                                        <th>Jenis</th>
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
                                            <span class="badge {{ $leave->tipe_izin == 'sakit' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ ucfirst($leave->tipe_izin) }}</span>
                                            @if($leave->file_bukti) <a href="{{ asset($leave->file_bukti) }}" target="_blank" class="ms-1"><i class="bi bi-paperclip"></i></a> @endif
                                        </td>
                                        <td class="text-truncate" style="max-width: 150px;">{{ $leave->deskripsi }}</td>
                                        <td>
                                            @if($isOwnData)
                                                <span class="badge bg-secondary fst-italic"><i class="bi bi-hourglass-split me-1"></i> Menunggu Approval</span>
                                            @else
                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#izin{{ $leave->leave_id }}">Proses</button>

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
                                                                                  oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-\/]/g, '')"></textarea>
                                                                        <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, strip, dan garis miring.</div>
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
                                    <tr><td colspan="5" class="text-center text-muted py-5">Tidak ada pengajuan izin.</td></tr>
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
    .dark-mode .btn-link.text-muted {
        color: #adb5bd !important;
    }
    
    .dark-mode .btn-link.text-muted:hover {
        color: #fff !important;
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