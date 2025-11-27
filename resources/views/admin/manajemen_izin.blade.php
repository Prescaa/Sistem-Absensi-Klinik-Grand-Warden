@extends('layouts.admin_app')

@section('page-title', 'Manajemen Izin')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-0">
            <h5 class="mb-0 fw-bold text-dark-emphasis">Data Pengajuan Izin & Cuti</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIzinModal">
                <i class="bi bi-plus-lg me-2"></i>Tambah Izin
            </button>
        </div>
        <div class="card-body p-0 table-responsive">

            {{-- Notifikasi hanya dari layout, blok ini kosong --}}

            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Karyawan</th>
                        <th>Tipe</th>
                        <th>Tanggal</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Bukti</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $leave)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark-emphasis">{{ $leave->employee->nama ?? '-' }}</div>
                            <div class="small text-muted">{{ $leave->employee->nip ?? '-' }}</div>
                        </td>
                        <td>
                            @if($leave->tipe_izin == 'sakit')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Sakit</span>
                            @elseif($leave->tipe_izin == 'cuti')
                                <span class="badge bg-info bg-opacity-10 text-info border border-info">Cuti</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Izin</span>
                            @endif
                        </td>
                        <td>
                            <div class="small text-dark-emphasis">
                                {{ $leave->tanggal_mulai->format('d M') }} - {{ $leave->tanggal_selesai->format('d M Y') }}
                            </div>
                            <small class="text-muted">
                                ({{ $leave->tanggal_mulai->diffInDays($leave->tanggal_selesai) + 1 }} Hari)
                            </small>
                        </td>
                        <td>
                            <span class="d-inline-block text-truncate" style="max-width: 150px;" title="{{ $leave->deskripsi }}">
                                {{ $leave->deskripsi ?? '-' }}
                            </span>
                        </td>
                        <td>
                            @if($leave->status == 'disetujui')
                                <span class="badge bg-success">Disetujui</span>
                            @elseif($leave->status == 'ditolak')
                                <span class="badge bg-danger">Ditolak</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($leave->file_bukti)
                                <a href="{{ asset($leave->file_bukti) }}" target="_blank" class="btn btn-sm btn-light border">
                                    <i class="bi bi-paperclip"></i>
                                </a>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <button class="btn btn-sm btn-outline-warning me-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editIzinModal"
                                    data-id="{{ $leave->leave_id }}"
                                    data-nama="{{ $leave->employee->nama ?? '-' }}"
                                    data-tipe="{{ $leave->tipe_izin }}"
                                    data-mulai="{{ $leave->tanggal_mulai->format('Y-m-d') }}"
                                    data-selesai="{{ $leave->tanggal_selesai->format('Y-m-d') }}"
                                    data-deskripsi="{{ $leave->deskripsi }}"
                                    data-status="{{ $leave->status }}"
                                    data-catatan="{{ $leave->catatan_admin }}">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteIzinModal"
                                    data-id="{{ $leave->leave_id }}"
                                    data-info="{{ $leave->employee->nama ?? '' }} - {{ ucfirst($leave->tipe_izin) }}">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Belum ada data izin.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="addIzinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Tambah Izin Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.izin.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Karyawan*</label>
                            <select name="emp_id" class="form-select" required>
                                <option value="">-- Pilih Karyawan --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->emp_id }}">{{ $emp->nama }} ({{ $emp->nip }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Tipe Izin*</label>
                            <select name="tipe_izin" class="form-select" required>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                                <option value="cuti">Cuti</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Tanggal Mulai*</label>
                            <input type="date" name="tanggal_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Tanggal Selesai*</label>
                            <input type="date" name="tanggal_selesai" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small">Alasan / Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Status Persetujuan</label>
                            <select name="status" class="form-select">
                                <option value="disetujui" selected>Disetujui</option>
                                <option value="pending">Pending</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Upload Bukti (Opsional)</label>
                            <input type="file" name="file_bukti" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="editIzinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">Edit Data Izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Karyawan (Read-only)</label>
                        <input type="text" id="edit_nama" class="form-control bg-light" readonly>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Tipe Izin</label>
                            <select name="tipe_izin" id="edit_tipe" class="form-select" required>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                                <option value="cuti">Cuti</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Mulai</label>
                            <input type="date" name="tanggal_mulai" id="edit_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Selesai</label>
                            <input type="date" name="tanggal_selesai" id="edit_selesai" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small">Alasan</label>
                            <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-12 border-top pt-3 mt-2">
                            <h6 class="fw-bold text-primary small mb-3">Status & Persetujuan</h6>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="disetujui">Disetujui</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Catatan Admin</label>
                            <input type="text" name="catatan_admin" id="edit_catatan" class="form-control" placeholder="Opsional...">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small">Ganti Bukti (Opsional)</label>
                           <input type="file"
                                name="file_bukti"
                                class="form-control"
                                accept=".jpg, .jpeg, .png, .pdf, image/jpeg, image/png, application/pdf">
                            <small class="text-muted">Format yang diterima: JPG, PNG, PDF. Maks 2MB. (Opsional)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DELETE --}}
<div class="modal fade" id="deleteIzinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Hapus Data Izin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteForm" method="POST">
                @csrf @method('DELETE')
                <div class="modal-body text-center p-4">
                    <i class="bi bi-trash-fill text-danger display-1 mb-3"></i>
                    <p class="mb-1">Anda yakin ingin menghapus data ini?</p>
                    <strong id="delete_info" class="d-block text-dark"></strong>
                    <small class="text-muted d-block mt-2">Data tidak dapat dikembalikan.</small>
                </div>
                <div class="modal-footer bg-light justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Modal Populate
        var editModal = document.getElementById('editIzinModal');
        if(editModal){
            editModal.addEventListener('show.bs.modal', function(event) {
                var btn = event.relatedTarget;
                var id = btn.getAttribute('data-id');

                document.getElementById('editForm').action = '/admin/manajemen-izin/update/' + id;
                document.getElementById('edit_nama').value = btn.getAttribute('data-nama');
                document.getElementById('edit_tipe').value = btn.getAttribute('data-tipe');
                document.getElementById('edit_mulai').value = btn.getAttribute('data-mulai');
                document.getElementById('edit_selesai').value = btn.getAttribute('data-selesai');
                document.getElementById('edit_deskripsi').value = btn.getAttribute('data-deskripsi');
                document.getElementById('edit_status').value = btn.getAttribute('data-status');
                document.getElementById('edit_catatan').value = btn.getAttribute('data-catatan');
            });
        }

        // Delete Modal Populate
        var deleteModal = document.getElementById('deleteIzinModal');
        if(deleteModal){
            deleteModal.addEventListener('show.bs.modal', function(event) {
                var btn = event.relatedTarget;
                var id = btn.getAttribute('data-id');
                document.getElementById('deleteForm').action = '/admin/manajemen-izin/destroy/' + id;
                document.getElementById('delete_info').textContent = btn.getAttribute('data-info');
            });
        }
    });
</script>
@endpush

@push('styles')
<style>
    /* CSS DARK MODE IZIN */
    .dark-mode .card {
        background-color: #1e1e1e !important;
        border-color: #333 !important;
        color: #e0e0e0 !important;
    }
    .dark-mode .card-header {
        background-color: #252525 !important;
        border-bottom-color: #333 !important;
        color: #fff !important;
    }
    .dark-mode .bg-white {
        background-color: #1e1e1e !important;
        color: #fff !important;
    }
    .dark-mode .table {
        color: #e0e0e0 !important;
        border-color: #444 !important;
    }
    .dark-mode .table-light th {
        background-color: #252525 !important;
        border-color: #444 !important;
        color: #fff !important;
    }
    .dark-mode .table tbody td {
        border-bottom-color: #333 !important;
        background-color: #1e1e1e !important;
        color: #e0e0e0 !important; /* PERBAIKAN: Warna teks dalam sel */
    }
    .dark-mode .table-hover tbody tr:hover td {
        background-color: #2a2a2a !important;
        color: #fff !important;
    }
    .dark-mode .text-dark-emphasis {
        color: #fff !important;
    }
    .dark-mode .text-muted {
        color: #aaa !important;
    }

    /* PERBAIKAN UTAMA: Kolom Alasan dan konten teks */
    .dark-mode td span.text-truncate {
        color: #e0e0e0 !important;
    }
    .dark-mode .badge.bg-secondary {
        background-color: #6c757d !important;
        color: #fff !important;
    }
    .dark-mode .badge.bg-success {
        background-color: #198754 !important;
        color: #fff !important;
    }
    .dark-mode .badge.bg-danger {
        background-color: #dc3545 !important;
        color: #fff !important;
    }

    /* Badge dengan opacity untuk dark mode */
    .dark-mode .badge.bg-danger.bg-opacity-10 {
        background-color: rgba(220, 53, 69, 0.15) !important;
        color: #ff6b7a !important;
        border-color: #dc3545 !important;
    }
    .dark-mode .badge.bg-info.bg-opacity-10 {
        background-color: rgba(13, 202, 240, 0.15) !important;
        color: #6edff6 !important;
        border-color: #0dcaf0 !important;
    }
    .dark-mode .badge.bg-warning.bg-opacity-10 {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #ffd352 !important;
        border-color: #ffc107 !important;
    }

    /* Modal Styles */
    .dark-mode .modal-content {
        background-color: #1e1e1e !important;
        color: #fff !important;
        border-color: #444 !important;
    }
    .dark-mode .modal-header {
        border-bottom-color: #444 !important;
    }
    .dark-mode .modal-footer, .dark-mode .bg-light {
        background-color: #252525 !important;
        border-top-color: #333 !important;
        color: #e0e0e0 !important;
    }

    /* Form Controls */
    .dark-mode .form-control, .dark-mode .form-select {
        background-color: #2b2b2b !important;
        border-color: #444 !important;
        color: #fff !important;
    }
    .dark-mode .form-control:focus, .dark-mode .form-select:focus {
        background-color: #2b2b2b !important;
        border-color: #0d6efd !important;
        color: #fff !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    .dark-mode .form-control::placeholder {
        color: #888 !important;
    }
    .dark-mode .form-control.bg-light {
        background-color: #333 !important;
        color: #ccc !important;
    }
    .dark-mode input[type="date"] {
        color-scheme: dark;
    }

    /* Buttons */
    .dark-mode .btn-close {
        filter: invert(1);
    }
    .dark-mode .btn-light {
        background-color: #333 !important;
        border-color: #444 !important;
        color: #fff !important;
    }
    .dark-mode .btn-light:hover {
        background-color: #444 !important;
        border-color: #555 !important;
    }
    .dark-mode .btn-outline-warning {
        color: #ffc107 !important;
        border-color: #ffc107 !important;
    }
    .dark-mode .btn-outline-warning:hover {
        background-color: #ffc107 !important;
        color: #000 !important;
    }
    .dark-mode .btn-outline-danger {
        color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    .dark-mode .btn-outline-danger:hover {
        background-color: #dc3545 !important;
        color: #fff !important;
    }

    /* Text dalam modal delete */
    .dark-mode .modal-body strong.text-dark {
        color: #fff !important;
    }
    .dark-mode .modal-body p {
        color: #e0e0e0 !important;
    }

    /* Border dan separator */
    .dark-mode .border-top {
        border-top-color: #444 !important;
    }
    .dark-mode .text-primary {
        color: #6ea8fe !important;
    }

    /* Label form */
    .dark-mode .form-label {
        color: #e0e0e0 !important;
    }

    /* Empty state */
    .dark-mode .text-muted {
        color: #888 !important;
    }
</style>
@endpush
