{{-- Menggunakan layout admin --}}
@extends('layouts.admin_app')

{{-- Mengatur judul halaman --}}
@section('page-title', 'Manajemen Karyawan')

{{-- Konten utama halaman --}}
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Karyawan</h5>
            <!-- Tombol untuk memicu Modal Tambah Karyawan -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKaryawanModal">
                <i class="bi bi-plus-circle-fill me-2"></i>Tambah Karyawan
            </button>
        </div>
        <div class="card-body">

            <!-- Menampilkan notifikasi error validasi (jika ada) -->
            @if ($errors->any())
                <div class="alert alert-danger pb-0">
                    <h5 class="alert-heading">Terjadi Kesalahan!</h5>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Tabel untuk menampilkan data (READ) -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">NIP</th>
                            <th scope="col">Departemen</th>
                            <th scope="col">Posisi</th>
                            <th scope="col">Username</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                        <!-- Loop data karyawan dari controller -->
                        @forelse ($employee as $emp)
                        <tr>
                            <td>{{ $emp->nama }}</td>
                            <td>{{ $emp->nip }}</td>
                            <td>{{ $emp->departemen ?? '-' }}</td>
                            <td>{{ $emp->posisi ?? '-' }}</td>
                            <!-- Pastikan relasi 'user' ada -->
                            <td>{{ $emp->user->username ?? 'N/A' }}</td>
                            <td>
                                @if($emp->status_aktif)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-danger">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <!-- Tombol Edit: Memicu Modal Edit -->
                                <button type="button" class="btn btn-sm btn-outline-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editKaryawanModal"
                                        data-id="{{ $emp->user->user_id }}"
                                        data-nama="{{ $emp->nama }}"
                                        data-nip="{{ $emp->nip }}"
                                        data-departemen="{{ $emp->departemen }}"
                                        data-posisi="{{ $emp->posisi }}"
                                        data-username="{{ $emp->user->username }}">
                                    <i class="bi bi-pencil-fill"></i> Edit
                                </button>

                                <!-- Tombol Delete: Memicu Modal Delete -->
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteKaryawanModal"
                                        data-id="{{ $emp->user->user_id }}"
                                        data-nama="{{ $emp->nama }}">
                                    <i class="bi bi-trash-fill"></i> Hapus
                                </button>
                            </td>
                        </tr>
                        @empty
                        <!-- Jika data karyawan kosong -->
                        <tr>
                            <td colspan="7" class="text-center text-muted p-4">
                                Belum ada data karyawan.
                            </td>
                        </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!-- === MODAL TAMBAH KARYAWAN (CREATE) === -->
<!-- ================================================== -->
<div class="modal fade" id="addKaryawanModal" tabindex="-1" aria-labelledby="addKaryawanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addKaryawanModalLabel">Tambah Karyawan Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <!-- Form Create -->
      <form action="{{ url('/admin/manajemen-karyawan/store') }}" method="POST">
          @csrf
          <div class="modal-body">
              <div class="row">
                  <!-- Data Employee -->
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Nama Lengkap*</label>
                      <input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">NIP*</label>
                      <input type="text" name="nip" class="form-control" value="{{ old('nip') }}" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Departemen</label>
                      <input type="text" name="departemen" class="form-control" value="{{ old('departemen') }}">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Posisi</label>
                      <input type="text" name="posisi" class="form-control" value="{{ old('posisi') }}">
                  </div>

                  <hr class="my-3">
                  <h6 class="fw-bold">Akun Login</h6>

                  <!-- Data User -->
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Username*</label>
                      <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <!-- (Kosong untuk perataan) -->
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Password*</label>
                      <input type="password" name="password" class="form-control" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Konfirmasi Password*</label>
                      <input type="password" name="password_confirmation" class="form-control" required>
                  </div>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Karyawan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- ================================================== -->
<!-- === MODAL EDIT KARYAWAN (UPDATE) === -->
<!-- ================================================== -->
<div class="modal fade" id="editKaryawanModal" tabindex="-1" aria-labelledby="editKaryawanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editKaryawanModalLabel">Edit Data Karyawan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <!-- Form Update -->
      <!-- Action form akan diisi oleh JavaScript -->
      <form id="editForm" method="POST">
          @csrf
          @method('PUT') <!-- Method spoofing untuk update -->
          <div class="modal-body">
              <div class="row">
                  <!-- Data Employee -->
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Nama Lengkap*</label>
                      <input type="text" id="edit_nama" name="nama" class="form-control" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">NIP*</label>
                      <input type="text" id="edit_nip" name="nip" class="form-control" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Departemen</label>
                      <input type="text" id="edit_departemen" name="departemen" class="form-control">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Posisi</label>
                      <input type="text" id="edit_posisi" name="posisi" class="form-control">
                  </div>

                  <hr class="my-3">
                  <h6 class="fw-bold">Akun Login</h6>

                  <!-- Data User -->
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Username*</label>
                      <input type="text" id="edit_username" name="username" class="form-control" required>
                  </div>
                  <div class="col-md-6 mb-3">
                      <!-- (Kosong untuk perataan) -->
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Password Baru</label>
                      <input type="password" name="password" class="form-control" placeholder="(Kosongkan jika tidak diubah)">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Konfirmasi Password Baru</label>
                      <input type="password" name="password_confirmation" class="form-control">
                  </div>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-warning">Update Data</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- ================================================== -->
<!-- === MODAL HAPUS KARYAWAN (DELETE) === -->
<!-- ================================================== -->
<div class="modal fade" id="deleteKaryawanModal" tabindex="-1" aria-labelledby="deleteKaryawanModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteKaryawanModalLabel">Hapus Karyawan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <!-- Form Delete -->
      <!-- Action form akan diisi oleh JavaScript -->
      <form id="deleteForm" method="POST">
          @csrf
          @method('DELETE') <!-- Method spoofing untuk hapus -->
          <div class="modal-body">
                <p>Anda yakin ingin menghapus karyawan: <strong id="delete_nama"></strong>?</p>
                <p class="text-danger">Tindakan ini tidak dapat dibatalkan. Akun login yang terkait juga akan dihapus.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger">Ya, Hapus Karyawan</button>
          </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<!-- Script untuk mengisi data modal Edit dan Delete -->
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // --- Script untuk Modal Edit ---
        var editModal = document.getElementById('editKaryawanModal');
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

        // --- Script untuk Modal Delete ---
        var deleteModal = document.getElementById('deleteKaryawanModal');
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
    });
</script>
@endpush
