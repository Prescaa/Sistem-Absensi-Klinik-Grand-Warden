@extends('layouts.admin_app')

@section('page-title', 'Manajemen Absensi')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-0">
            <h5 class="mb-0 fw-bold text-dark-emphasis">Data Riwayat Absensi</h5>
            {{-- Tombol Tambah Manual (Opsional/Disembunyikan) --}}
            {{-- <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAbsensiModal">
                <i class="bi bi-plus-lg me-2"></i>Tambah Absensi Manual
            </button> --}}
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

                                    @elseif($att->validation->status_validasi_final == 'Pending')
                                        <span class="badge bg-secondary text-light"><i class="bi bi-hourglass-split me-1"></i>Pending</span>

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
                                {{-- TOMBOL LIHAT DETAIL (PENGGANTI DELETE) --}}
                                <button class="btn btn-sm btn-outline-info"
                                        data-bs-toggle="modal"
                                        data-bs-target="#detailAbsensiModal"
                                        data-nama="{{ $att->employee->nama ?? '-' }}"
                                        data-nip="{{ $att->employee->nip ?? '-' }}"
                                        data-waktu="{{ $att->waktu_unggah->translatedFormat('l, d F Y H:i') }}"
                                        data-type="{{ ucfirst($att->type) }}"
                                        data-foto="{{ $att->nama_file_foto ? asset($att->nama_file_foto) : '' }}"
                                        data-status="{{ $att->validation->status_validasi_final ?? 'Pending' }}"
                                        data-catatan="{{ $att->validation->catatan_admin ?? '-' }}"
                                        data-lat="{{ $att->latitude }}"
                                        data-long="{{ $att->longitude }}">
                                    <i class="bi bi-eye-fill"></i> Detail
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

{{-- MODAL DETAIL ABSENSI --}}
<div class="modal fade" id="detailAbsensiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i>Detail Absensi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Foto Besar --}}
                <div class="bg-light text-center p-4 border-bottom position-relative">
                    <img id="detail_foto" src="" alt="Bukti Absensi" class="img-fluid rounded shadow-sm" style="max-height: 300px; object-fit: contain;">
                    <div class="mt-3">
                        <span id="detail_status_badge" class="badge bg-secondary fs-6 px-3 py-2 rounded-pill">Pending</span>
                    </div>
                </div>

                {{-- Informasi Detail --}}
                <div class="p-4">
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold">Nama Karyawan</small>
                            <span id="detail_nama" class="fs-5 text-dark-emphasis">Nama</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold">NIP</small>
                            <span id="detail_nip" class="fs-5 text-dark-emphasis">12345</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold">Waktu Absen</small>
                            <span id="detail_waktu">Senin, 01 Jan 2025 08:00</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold">Tipe</small>
                            <span id="detail_type" class="fw-bold text-primary">Masuk</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block fw-bold">Lokasi (Koordinat)</small>
                        <div class="d-flex align-items-center justify-content-between bg-light p-2 rounded border">
                            <span id="detail_lokasi" class="font-monospace small">0,0</span>
                            <a id="detail_map_link" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-map-fill me-1"></i> Buka Map
                            </a>
                        </div>
                    </div>

                    <div class="mb-0">
                        <small class="text-muted d-block fw-bold">Catatan Validasi</small>
                        <div class="alert alert-light border text-dark-emphasis mb-0" id="detail_catatan">
                            -
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH --}}
{{--<div class="modal fade" id="addAbsensiModal" tabindex="-1" aria-hidden="true">
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
                        <select name="emp_id" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->emp_id }}">{{ $emp->nama }} ({{ $emp->nip }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small">Waktu Absen</label>
                            <input type="datetime-local" name="waktu_unggah" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small">Tipe</label>
                            <select name="type" class="form-select" required>
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
                        <input type="text" name="catatan_admin" class="form-control form-control-sm" placeholder="Catatan admin (opsional)"
                               oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-\/]/g, '')">
                        <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, strip, dan garis miring.</div>
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
</div>--}}

@endsection

@push('styles')
<style>
    /* CSS Dark Mode Absensi */
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
    }
    .dark-mode .table-hover tbody tr:hover td {
        background-color: #2a2a2a !important;
    }
    .dark-mode .text-dark-emphasis { color: #fff !important; }

    /* Modal Dark Mode */
    .dark-mode .modal-content {
        background-color: #1e1e1e !important;
        color: #fff !important;
    }
    .dark-mode .modal-footer, .dark-mode .bg-light {
        background-color: #252525 !important;
        border-color: #333 !important;
        color: #e0e0e0 !important;
    }
    .dark-mode .btn-close { filter: invert(1); }
    
    .dark-mode .alert-light {
        background-color: #2b2b2b !important;
        border-color: #444 !important;
        color: #ddd !important;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Logic untuk Modal Detail
        var detailModal = document.getElementById('detailAbsensiModal');
        if(detailModal){
            detailModal.addEventListener('show.bs.modal', function(event) {
                var btn = event.relatedTarget;
                
                // Ambil data dari atribut tombol
                var nama = btn.getAttribute('data-nama');
                var nip = btn.getAttribute('data-nip');
                var waktu = btn.getAttribute('data-waktu');
                var type = btn.getAttribute('data-type');
                var foto = btn.getAttribute('data-foto');
                var status = btn.getAttribute('data-status');
                var catatan = btn.getAttribute('data-catatan');
                var lat = btn.getAttribute('data-lat');
                var long = btn.getAttribute('data-long');

                // Isi elemen modal
                document.getElementById('detail_nama').textContent = nama;
                document.getElementById('detail_nip').textContent = nip;
                document.getElementById('detail_waktu').textContent = waktu;
                document.getElementById('detail_type').textContent = type;
                document.getElementById('detail_catatan').textContent = catatan;
                document.getElementById('detail_lokasi').textContent = lat + ', ' + long;
                
                // Update Foto
                var imgEl = document.getElementById('detail_foto');
                if(foto) {
                    imgEl.src = foto;
                    imgEl.style.display = 'inline-block';
                } else {
                    imgEl.style.display = 'none';
                }

                // Update Map Link
                var mapLink = document.getElementById('detail_map_link');
                mapLink.href = 'https://maps.google.com/?q=' + lat + ',' + long;

                // Update Badge Status
                var badge = document.getElementById('detail_status_badge');
                badge.className = 'badge fs-6 px-3 py-2 rounded-pill'; // Reset class
                badge.textContent = status;

                if(status === 'Valid') {
                    badge.classList.add('bg-primary');
                } else if (status === 'Invalid') {
                    badge.classList.add('bg-danger');
                } else {
                    badge.classList.add('bg-secondary');
                }
            });
        }
    });
</script>
@endpush