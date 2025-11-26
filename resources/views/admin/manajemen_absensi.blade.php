@extends('layouts.admin_app')

@section('page-title', 'Manajemen Absensi')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-0">
            <h5 class="mb-0 fw-bold text-dark-emphasis">Data Riwayat Absensi</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAbsensiModal">
                <i class="bi bi-plus-lg me-2"></i>Tambah Absensi Manual
            </button>
        </div>
        <div class="card-body p-0">
            
            {{-- Notifikasi hanya dari layout --}}

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Waktu & Tanggal</th>
                            <th>Karyawan</th>
                            <th>Tipe</th>
                            <th>Foto</th>
                            <th>Validasi</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $att)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark-emphasis">{{ $att->waktu_unggah->format('H:i') }}</div>
                                <div class="small text-muted">{{ $att->waktu_unggah->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark-emphasis">{{ $att->employee->nama ?? 'Unknown' }}</div>
                                <div class="small text-muted">{{ $att->employee->nip ?? '-' }}</div>
                            </td>
                            <td>
                                @if($att->type == 'masuk')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Masuk</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning text-dark">Pulang</span>
                                @endif
                            </td>
                            <td>
                                @if($att->nama_file_foto)
                                    <a href="{{ asset($att->nama_file_foto) }}" target="_blank">
                                        <img src="{{ asset($att->nama_file_foto) }}" class="rounded border" width="40" height="40" style="object-fit:cover;">
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                @if($att->validation)
                                    @if($att->validation->status_validasi_final == 'Valid')
                                        <span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i>Valid</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Invalid</span>
                                    @endif
                                    @if($att->validation->catatan_admin)
                                        <div class="small text-muted fst-italic mt-1" style="font-size: 0.7rem; max-width: 150px;">
                                            {{ Str::limit($att->validation->catatan_admin, 30) }}
                                        </div>
                                    @endif
                                @else
                                    <span class="badge bg-secondary text-light">Pending</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                {{-- TOMBOL EDIT --}}
                                <button class="btn btn-sm btn-outline-warning me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAbsensiModal"
                                        data-id="{{ $att->att_id }}"
                                        data-nama="{{ $att->employee->nama ?? '-' }}"
                                        data-waktu="{{ $att->waktu_unggah->format('Y-m-d\TH:i') }}"
                                        data-type="{{ $att->type }}"
                                        data-foto="{{ asset($att->nama_file_foto) }}"
                                        data-status="{{ $att->validation->status_validasi_final ?? 'Pending' }}"
                                        data-catatan="{{ $att->validation->catatan_admin ?? '' }}">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>

                                {{-- TOMBOL HAPUS --}}
                                <button class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteAbsensiModal"
                                        data-id="{{ $att->att_id }}"
                                        data-info="{{ $att->employee->nama ?? '' }} - {{ $att->waktu_unggah->format('d M Y H:i') }}">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada data absensi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="addAbsensiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Tambah Absensi Manual</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.absensi.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih Karyawan</label>
                        {{-- VALIDASI PESAN --}}
                        <select name="emp_id" class="form-select" required
                                oninvalid="this.setCustomValidity('Silakan pilih karyawan dari daftar.')"
                                oninput="this.setCustomValidity('')">
                            <option value="">-- Pilih --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->emp_id }}">{{ $emp->nama }} ({{ $emp->nip }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small">Waktu Absen</label>
                            {{-- VALIDASI PESAN --}}
                            <input type="datetime-local" name="waktu_unggah" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}"
                                   oninvalid="this.setCustomValidity('Tentukan waktu absensi.')"
                                   oninput="this.setCustomValidity('')">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small">Tipe</label>
                            {{-- VALIDASI PESAN --}}
                            <select name="type" class="form-select" required
                                    oninvalid="this.setCustomValidity('Pilih tipe absensi (Masuk/Pulang).')"
                                    oninput="this.setCustomValidity('')">
                                <option value="masuk">Masuk</option>
                                <option value="pulang">Pulang</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border">
                        <label class="form-label fw-bold small text-primary">Status Validasi</label>
                        <select name="status_validasi" class="form-select mb-2">
                            <option value="Valid" selected>✅ Valid (Disetujui)</option>
                            <option value="Invalid">❌ Invalid (Ditolak)</option>
                            <option value="Pending">⏳ Pending (Menunggu)</option>
                        </select>
                        <input type="text" name="catatan_admin" class="form-control form-control-sm" placeholder="Catatan admin (opsional)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Upload Bukti Foto (Opsional)</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
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
<div class="modal fade" id="editAbsensiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">Edit Data Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Karyawan</label>
                        <input type="text" id="edit_nama" class="form-control bg-light" readonly>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small">Waktu Absen</label>
                            {{-- VALIDASI PESAN --}}
                            <input type="datetime-local" name="waktu_unggah" id="edit_waktu" class="form-control" required
                                   oninvalid="this.setCustomValidity('Tentukan waktu absensi.')"
                                   oninput="this.setCustomValidity('')">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small">Tipe</label>
                            {{-- VALIDASI PESAN --}}
                            <select name="type" id="edit_type" class="form-select" required
                                    oninvalid="this.setCustomValidity('Pilih tipe absensi.')"
                                    oninput="this.setCustomValidity('')">
                                <option value="masuk">Masuk</option>
                                <option value="pulang">Pulang</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border">
                        <label class="form-label fw-bold small text-primary">Update Status Validasi</label>
                        <select name="status_validasi" id="edit_status" class="form-select mb-2">
                            <option value="Valid">✅ Valid</option>
                            <option value="Invalid">❌ Invalid</option>
                            <option value="Pending">⏳ Pending</option>
                        </select>
                        <textarea name="catatan_admin" id="edit_catatan" class="form-control form-control-sm" rows="2" placeholder="Catatan admin..."></textarea>
                    </div>

                    <div class="mb-3 p-2 border rounded d-flex gap-3 align-items-center">
                         <img id="edit_preview_foto" src="" width="50" height="50" class="rounded border bg-white">
                         <div class="flex-grow-1">
                             <label class="form-label fw-bold small mb-1">Ganti Foto (Opsional)</label>
                             <input type="file" name="foto" class="form-control form-control-sm" accept="image/*">
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
<div class="modal fade" id="deleteAbsensiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Hapus Data Absensi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteForm" method="POST">
                @csrf @method('DELETE')
                <div class="modal-body text-center p-4">
                    <i class="bi bi-trash-fill text-danger display-1 mb-3"></i>
                    <p class="mb-1">Anda yakin ingin menghapus data absensi ini?</p>
                    <strong id="delete_info" class="d-block text-dark"></strong>
                    <small class="text-muted d-block mt-2">Validasi terkait juga akan terhapus permanen.</small>
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
        // Edit Modal Logic
        var editModal = document.getElementById('editAbsensiModal');
        if(editModal){
            editModal.addEventListener('show.bs.modal', function(event) {
                var btn = event.relatedTarget;
                var id = btn.getAttribute('data-id');

                document.getElementById('editForm').action = '/admin/manajemen-absensi/update/' + id;
                document.getElementById('edit_nama').value = btn.getAttribute('data-nama');
                document.getElementById('edit_waktu').value = btn.getAttribute('data-waktu');
                document.getElementById('edit_type').value = btn.getAttribute('data-type');

                var fotoUrl = btn.getAttribute('data-foto');
                document.getElementById('edit_preview_foto').src = fotoUrl ? fotoUrl : 'https://via.placeholder.com/50?text=No+Img';

                document.getElementById('edit_status').value = btn.getAttribute('data-status');
                document.getElementById('edit_catatan').value = btn.getAttribute('data-catatan');
            });
        }

        // Delete Modal Logic
        var deleteModal = document.getElementById('deleteAbsensiModal');
        if(deleteModal){
            deleteModal.addEventListener('show.bs.modal', function(event) {
                var btn = event.relatedTarget;
                var id = btn.getAttribute('data-id');
                document.getElementById('deleteForm').action = '/admin/manajemen-absensi/destroy/' + id;
                document.getElementById('delete_info').textContent = btn.getAttribute('data-info');
            });
        }
    });
</script>
@endpush