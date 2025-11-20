{{-- Menggunakan layout admin --}}
@extends('layouts.admin_app')

{{-- Mengatur judul halaman --}}
@section('page-title', 'Manajemen Karyawan')

{{-- Konten utama halaman --}}
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-0">
            <h5 class="mb-0 fw-bold text-dark-emphasis">Daftar Karyawan</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKaryawanModal">
                <i class="bi bi-plus-circle-fill me-2"></i>Tambah Karyawan
            </button>
        </div>
        <div class="card-body p-0">

            @if ($errors->any())
                <div class="alert alert-danger m-3 pb-0">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi Kesalahan!</h5>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Nama</th>
                            <th scope="col">NIP</th>
                            <th scope="col">Departemen</th>
                            <th scope="col">Posisi</th>
                            <th scope="col">Username</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse ($employee as $emp)
                        <tr>
                            <td class="ps-4 fw-bold text-dark-emphasis">{{ $emp->nama }}</td>
                            <td class="text-dark-emphasis">{{ $emp->nip }}</td>
                            <td class="text-dark-emphasis">{{ $emp->departemen ?? '-' }}</td>
                            <td class="text-dark-emphasis">{{ $emp->posisi ?? '-' }}</td>
                            <td class="text-dark-emphasis">{{ $emp->user->username ?? 'N/A' }}</td>
                            <td>
                                @if($emp->status_aktif)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">Aktif</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 rounded-pill">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <button type="button" class="btn btn-sm btn-outline-warning me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editKaryawanModal"
                                        data-id="{{ $emp->user->user_id }}"
                                        data-nama="{{ $emp->nama }}"
                                        data-nip="{{ $emp->nip }}"
                                        data-departemen="{{ $emp->departemen }}"
                                        data-posisi="{{ $emp->posisi }}"
                                        data-username="{{ $emp->user->username }}">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteKaryawanModal"
                                        data-id="{{ $emp->user->user_id }}"
                                        data-nama="{{ $emp->nama }}">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted p-5">
                                <i class="bi bi-people display-1 mb-3 d-block text-secondary"></i>
                                <span class="fs-5">Belum ada data karyawan.</span>
                            </td>
                        </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Karyawan -->
<div class="modal fade" id="addKaryawanModal" tabindex="-1" aria-labelledby="addKaryawanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="addKaryawanModalLabel"><i class="bi bi-person-plus-fill me-2"></i>Tambah Karyawan Baru</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.karyawan.store') }}" method="POST">
          @csrf
          <div class="modal-body p-4">
              <div class="row">
                  <div class="col-12 mb-3">
                      <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Data Karyawan</h6>
                  </div>
                  
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Nama Lengkap*</label>
                      <input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">NIP*</label>
                      <input type="text" name="nip" class="form-control" value="{{ old('nip') }}" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Departemen</label>
                      <input type="text" name="departemen" class="form-control" value="{{ old('departemen') }}">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Posisi</label>
                      <input type="text" name="posisi" class="form-control" value="{{ old('posisi') }}">
                  </div>

                  <div class="col-12 mt-2 mb-3">
                      <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Akun Login</h6>
                  </div>

                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Username*</label>
                      <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                  </div>
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Password*</label>
                      <input type="password" name="password" class="form-control" required>
                  </div>
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Konfirmasi Password*</label>
                      <input type="password" name="password_confirmation" class="form-control" required>
                  </div>
              </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Karyawan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Karyawan -->
<div class="modal fade" id="editKaryawanModal" tabindex="-1" aria-labelledby="editKaryawanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title fw-bold" id="editKaryawanModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Data Karyawan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editForm" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-body p-4">
              <div class="row">
                  <div class="col-12 mb-3">
                      <h6 class="text-warning fw-bold border-bottom pb-2 mb-3 text-dark">Data Karyawan</h6>
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Nama Lengkap*</label>
                      <input type="text" id="edit_nama" name="nama" class="form-control" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">NIP*</label>
                      <input type="text" id="edit_nip" name="nip" class="form-control" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Departemen</label>
                      <input type="text" id="edit_departemen" name="departemen" class="form-control">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Posisi</label>
                      <input type="text" id="edit_posisi" name="posisi" class="form-control">
                  </div>

                  <div class="col-12 mt-2 mb-3">
                      <h6 class="text-warning fw-bold border-bottom pb-2 mb-3 text-dark">Akun Login</h6>
                  </div>

                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Username*</label>
                      <input type="text" id="edit_username" name="username" class="form-control" required>
                  </div>
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Password Baru</label>
                      <input type="password" name="password" class="form-control" placeholder="(Kosongkan jika tidak diubah)">
                  </div>
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Konf. Password Baru</label>
                      <input type="password" name="password_confirmation" class="form-control">
                  </div>
              </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-warning px-4 fw-bold">Update Data</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Hapus Karyawan -->
<div class="modal fade" id="deleteKaryawanModal" tabindex="-1" aria-labelledby="deleteKaryawanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold" id="deleteKaryawanModalLabel"><i class="bi bi-trash-fill me-2"></i>Hapus Karyawan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="deleteForm" method="POST">
          @csrf
          @method('DELETE') 
          <div class="modal-body p-4 text-center">
                <i class="bi bi-exclamation-circle text-danger display-1 mb-3"></i>
                <p class="fs-5 text-dark-emphasis">Anda yakin ingin menghapus karyawan:</p>
                <h4 class="fw-bold text-dark-emphasis" id="delete_nama"></h4>
                <p class="text-muted mt-3 small">Tindakan ini <strong>tidak dapat dibatalkan</strong>. Akun login dan semua data absensi karyawan ini juga akan dihapus permanen.</p>
          </div>
          <div class="modal-footer bg-light justify-content-center">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger px-4 fw-bold">Ya, Hapus Karyawan</button>
          </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
    /* === CSS KHUSUS UNTUK MEMPERCANTIK TABEL DI DARK MODE === */
    
    /* Card di Dark Mode */
    .dark-mode .card {
        background-color: #1e1e1e !important;
        border-color: #333 !important;
    }
    .dark-mode .card-header {
        background-color: #252525 !important;
        border-bottom-color: #333 !important;
        color: #fff !important;
    }
    .dark-mode .card-body {
        background-color: #1e1e1e !important;
    }

    /* Modal di Dark Mode */
    .dark-mode .modal-content {
        background-color: #1e1e1e !important;
        color: #e0e0e0;
        border: 1px solid #444;
    }
    .dark-mode .modal-header {
        border-bottom-color: #444;
    }
    .dark-mode .modal-footer {
        background-color: #252525 !important;
        border-top-color: #444 !important;
    }
    .dark-mode .btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    /* Form Control di Dark Mode */
    .dark-mode .form-control {
        background-color: #2b2b2b;
        border-color: #444;
        color: #fff;
    }
    .dark-mode .form-control:focus {
        background-color: #333;
        color: #fff;
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    .dark-mode .form-label {
        color: #ccc;
    }

    /* Tabel di Dark Mode */
    .dark-mode .table {
        color: #e0e0e0;
        border-color: #444;
    }
    .dark-mode .table-light th {
        background-color: #333 !important;
        color: #fff !important;
        border-color: #444 !important;
    }
    .dark-mode .table-hover tbody tr:hover {
        background-color: #2a2a2a !important;
        color: #fff;
    }
    .dark-mode .table tbody tr {
        border-bottom-color: #333 !important;
    }
    .dark-mode .table td {
        border-bottom-color: #333 !important;
        background-color: #1e1e1e !important;
    }
    
    /* Text utilities override untuk dark mode */
    .dark-mode .text-dark-emphasis { 
        color: #e0e0e0 !important; 
    }
    .dark-mode .text-muted { 
        color: #aaa !important; 
    }
    .dark-mode .text-secondary {
        color: #888 !important;
    }
    
    /* Badge di dark mode */
    .dark-mode .badge.bg-success {
        background-color: rgba(25, 135, 84, 0.2) !important;
        color: #75b798 !important;
        border-color: #75b798 !important;
    }
    .dark-mode .badge.bg-danger {
        background-color: rgba(220, 53, 69, 0.2) !important;
        color: #e6858f !important;
        border-color: #e6858f !important;
    }
    
    /* Alert di dark mode */
    .dark-mode .alert-danger {
        background-color: rgba(220, 53, 69, 0.1) !important;
        border-color: #dc3545 !important;
        color: #e6858f !important;
    }
    
    /* Border utilities untuk dark mode */
    .dark-mode .border-bottom {
        border-bottom-color: #444 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // --- Script untuk Modal Edit ---
        var editModal = document.getElementById('editKaryawanModal');
        if(editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Tombol yang memicu modal

                // Ambil data dari atribut data-*
                var id = button.getAttribute('data-id');
                var nama = button.getAttribute('data-nama');
                var nip = button.getAttribute('data-nip');
                var departemen = button.getAttribute('data-departemen');
                var posisi = button.getAttribute('data-posisi');
                var username = button.getAttribute('data-username');

                // Atur action form update
                var form = document.getElementById('editForm');
                form.action = '/admin/manajemen-karyawan/update/' + id;

                // Isi field form di dalam modal
                document.getElementById('edit_nama').value = nama;
                document.getElementById('edit_nip').value = nip;
                document.getElementById('edit_departemen').value = departemen;
                document.getElementById('edit_posisi').value = posisi;
                document.getElementById('edit_username').value = username;
            });
        }

        // --- Script untuk Modal Delete ---
        var deleteModal = document.getElementById('deleteKaryawanModal');
        if(deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Tombol yang memicu modal

                // Ambil data
                var id = button.getAttribute('data-id');
                var nama = button.getAttribute('data-nama');

                // Atur action form delete
                var form = document.getElementById('deleteForm');
                form.action = '/admin/manajemen-karyawan/destroy/' + id;

                // Isi nama di modal konfirmasi
                document.getElementById('delete_nama').textContent = nama;
            });
        }
    });
</script>
@endpush