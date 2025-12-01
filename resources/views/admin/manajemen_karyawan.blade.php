@extends('layouts.admin_app')

@section('page-title', 'Manajemen Pengguna')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-0">
            <h5 class="mb-0 fw-bold text-dark-emphasis">Daftar Pengguna & Profil</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKaryawanModal">
                <i class="bi bi-plus-circle-fill me-2"></i>Tambah Pengguna
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
                            <th scope="col" class="ps-4">Foto</th>
                            <th scope="col">Nama / NIP</th>
                            <th scope="col">Kontak & Info</th>
                            <th scope="col">Role & Akun</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee as $emp)
                        <tr>
                            {{-- KOLOM 1: FOTO PROFIL --}}
                            <td class="ps-4">
                                @if($emp->foto_profil)
                                    <img src="{{ asset($emp->foto_profil) }}" alt="Foto"
                                         class="rounded-circle shadow-sm object-fit-cover border"
                                         width="45" height="45">
                                @else
                                    <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center shadow-sm"
                                         style="width: 45px; height: 45px; font-weight: bold; font-size: 1.2rem;">
                                        {{ strtoupper(substr($emp->nama, 0, 1)) }}
                                    </div>
                                @endif
                            </td>

                            {{-- KOLOM 2: NAMA & NIP --}}
                            <td>
                                <div class="fw-bold text-dark-emphasis">{{ $emp->nama }}</div>
                                <div class="small text-muted">{{ $emp->nip }}</div>
                            </td>

                            {{-- KOLOM 3: KONTAK (DEPT, POSISI, TELP) --}}
                            <td>
                                <div class="text-dark-emphasis mb-1 small">
                                    <i class="bi bi-building me-1 text-primary"></i> {{ $emp->departemen ?? '-' }}
                                    <span class="text-muted">({{ $emp->posisi ?? '-' }})</span>
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-telephone me-1"></i> {{ $emp->no_telepon ?? '-' }}
                                </div>
                            </td>

                            {{-- KOLOM 4: ROLE & USERNAME --}}
                            <td>
                                <div class="mb-1">
                                    @php $role = $emp->user->role ?? 'Karyawan'; @endphp
                                    @if($role == 'Admin')
                                        <span class="badge bg-primary"><i class="bi bi-shield-lock-fill me-1"></i>Admin</span>
                                    @elseif($role == 'Manajemen')
                                        <span class="badge bg-info text-dark"><i class="bi bi-graph-up me-1"></i>Manajemen</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="bi bi-person me-1"></i>Karyawan</span>
                                    @endif
                                </div>
                                <div class="small text-muted"><i class="bi bi-person-badge me-1"></i> {{ $emp->user->username ?? 'N/A' }}</div>
                            </td>

                            {{-- KOLOM 5: STATUS --}}
                            <td>
                                @if($emp->status_aktif)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill">Aktif</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill">Non-Aktif</span>
                                @endif
                            </td>

                            {{-- KOLOM 6: AKSI --}}
                            <td class="text-center pe-4">
                                {{-- Tombol Edit: Kirim semua data via data-attributes --}}
                                <button type="button" class="btn btn-sm btn-outline-warning me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editKaryawanModal"
                                        data-id="{{ $emp->user->user_id }}"
                                        data-nama="{{ $emp->nama }}"
                                        data-nip="{{ $emp->nip }}"
                                        data-departemen="{{ $emp->departemen }}"
                                        data-posisi="{{ $emp->posisi }}"
                                        data-username="{{ $emp->user->username }}"
                                        data-role="{{ $emp->user->role }}"
                                        data-telepon="{{ $emp->no_telepon }}"
                                        data-alamat="{{ $emp->alamat }}"
                                        data-foto="{{ $emp->foto_profil ? asset($emp->foto_profil) : '' }}">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>

                                {{-- Tombol Hapus: Cek agar tidak hapus diri sendiri --}}
                                @if(Auth::check() && Auth::user()->user_id == $emp->user->user_id)
                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="Tidak bisa hapus akun sendiri">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteKaryawanModal"
                                            data-id="{{ $emp->user->user_id }}"
                                            data-nama="{{ $emp->nama }}">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted p-5">
                                <i class="bi bi-people display-1 mb-3 d-block text-secondary"></i>
                                Belum ada data pengguna.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ================= MODAL TAMBAH PENGGUNA ================= --}}
<div class="modal fade" id="addKaryawanModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Tambah Pengguna Baru</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      {{-- Form Upload menggunakan enctype --}}
      <form action="{{ route('admin.karyawan.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-body p-4">
              <div class="row">
                  <div class="col-12 mb-3">
                      <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Data Pribadi & Profil</h6>
                  </div>

                  {{-- Input Foto --}}
                  <div class="col-12 mb-4 text-center">
                      <div class="mb-2">
                        <label class="form-label fw-bold small text-dark-emphasis">Foto Profil</label>
                      </div>
                      <input type="file" name="foto_profil" class="form-control form-control-sm w-75 mx-auto" accept="image/*">
                      <div class="form-text small">Format: JPG, PNG. Maks 2MB.</div>
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Nama Lengkap*</label>
                      <input type="text" name="nama" class="form-control" required
                             oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                      <div class="form-text small">Hanya huruf dan spasi.</div>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">NIP*</label>
                      <input type="text" name="nip" class="form-control" required
                             oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                      <div class="form-text small">Hanya angka (0-9).</div>
                  </div>

                  {{-- Input Telepon --}}
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">No. Telepon / HP</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="no_telepon" class="form-control" placeholder="08..."
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                      </div>
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Departemen</label>
                      <input type="text" name="departemen" class="form-control"
                             oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-\/]/g, '')">
                      <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, strip, dan garis miring.</div>
                  </div>

                  {{-- Input Alamat --}}
                  <div class="col-md-12 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Alamat Lengkap</label>
                      <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat domisili..."
                                oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-\/]/g, '')"></textarea>
                      <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, strip, dan garis miring.</div>
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Posisi / Jabatan</label>
                      <input type="text" name="posisi" class="form-control"
                             oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-\/]/g, '')">
                      <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, strip, dan garis miring.</div>
                  </div>

                  <div class="col-12 mt-3 mb-3">
                      <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Akun & Keamanan</h6>
                  </div>

                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Role Akses*</label>
                      <select name="role" class="form-select" required>
                          <option value="Karyawan" selected>Karyawan</option>
                          <option value="Admin">Admin</option>
                          <option value="Manajemen">Manajemen</option>
                      </select>
                  </div>
                  <div class="col-md-8 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Username Login*</label>
                      <input type="text" name="username" class="form-control" required>
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Password*</label>
                      <div class="input-group">
                          <input type="password" name="password" id="addPass" class="form-control" required>
                          <span class="input-group-text cursor-pointer" onclick="togglePassword('addPass', this)"><i class="bi bi-eye"></i></span>
                      </div>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Konfirmasi Password*</label>
                      <div class="input-group">
                          <input type="password" name="password_confirmation" id="addPassConf" class="form-control" required>
                          <span class="input-group-text cursor-pointer" onclick="togglePassword('addPassConf', this)"><i class="bi bi-eye"></i></span>
                      </div>
                  </div>
              </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

{{-- ================= MODAL EDIT PENGGUNA ================= --}}
<div class="modal fade" id="editKaryawanModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Profil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      {{-- Form Edit menggunakan enctype --}}
      <form id="editForm" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <div class="modal-body p-4">
              <div class="row">
                  <div class="col-12 mb-3">
                      <h6 class="text-warning fw-bold border-bottom pb-2 mb-3 text-dark">Data Pribadi</h6>
                  </div>

                  {{-- Preview Foto & Input Ganti --}}
                  <div class="col-12 mb-4 d-flex align-items-center gap-3 bg-light p-3 rounded border">
                      <div class="text-center">
                          <label class="form-label fw-bold small d-block mb-1 text-dark-emphasis">Foto Saat Ini</label>
                          {{-- Image Preview: Src diisi via JS --}}
                          <img id="preview_foto" src="" alt="-" class="rounded-circle border shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                      </div>
                      <div class="flex-grow-1">
                          <label class="form-label fw-bold small text-dark-emphasis">Ganti Foto Baru</label>
                          <input type="file" name="foto_profil" class="form-control form-control-sm" accept="image/*">
                          <div class="form-text small">Biarkan kosong jika tidak ingin mengubah foto.</div>
                      </div>
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Nama Lengkap*</label>
                      <input type="text" id="edit_nama" name="nama" class="form-control" required
                             oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                      <div class="form-text small">Hanya huruf dan spasi.</div>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">NIP*</label>
                      <input type="text" id="edit_nip" name="nip" class="form-control" required
                             oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                      <div class="form-text small">Hanya angka (0-9).</div>
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">No. Telepon</label>
                      <input type="text" id="edit_telepon" name="no_telepon" class="form-control"
                             oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Departemen</label>
                      <input type="text" id="edit_departemen" name="departemen" class="form-control"
                             oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-\/]/g, '')">
                      <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, strip, dan garis miring.</div>
                  </div>

                  <div class="col-md-12 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Alamat Lengkap</label>
                      <textarea id="edit_alamat" name="alamat" class="form-control" rows="2"
                                oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-\/]/g, '')"></textarea>
                      <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, strip, dan garis miring.</div>
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Posisi</label>
                      <input type="text" id="edit_posisi" name="posisi" class="form-control"
                             oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s.,\-\/]/g, '')">
                      <div class="form-text small">Hanya huruf, angka, spasi, titik, koma, strip, dan garis miring.</div>
                  </div>

                  <div class="col-12 mt-3 mb-3">
                      <h6 class="text-warning fw-bold border-bottom pb-2 mb-3 text-dark">Akun & Akses</h6>
                  </div>

                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Role*</label>
                      <select name="role" id="edit_role" class="form-select" required>
                          <option value="Karyawan">Karyawan</option>
                          <option value="Admin">Admin</option>
                          <option value="Manajemen">Manajemen</option>
                      </select>
                  </div>
                  <div class="col-md-8 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Username*</label>
                      <input type="text" id="edit_username" name="username" class="form-control" required>
                  </div>

                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Reset Password</label>
                      <div class="input-group">
                          <input type="password" name="password" id="editPass" class="form-control" placeholder="(Kosongkan jika tetap)">
                          <span class="input-group-text cursor-pointer" onclick="togglePassword('editPass', this)"><i class="bi bi-eye"></i></span>
                      </div>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold small text-dark-emphasis">Konfirmasi Password</label>
                      <div class="input-group">
                          <input type="password" name="password_confirmation" id="editPassConf" class="form-control">
                          <span class="input-group-text cursor-pointer" onclick="togglePassword('editPassConf', this)"><i class="bi bi-eye"></i></span>
                      </div>
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

{{-- ================= MODAL HAPUS PENGGUNA ================= --}}
<div class="modal fade" id="deleteKaryawanModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-trash-fill me-2"></i>Hapus Pengguna</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="deleteForm" method="POST">
          @csrf @method('DELETE')
          <div class="modal-body p-4 text-center">
                <i class="bi bi-exclamation-circle text-danger display-1 mb-3"></i>
                <p class="fs-5 text-dark-emphasis">Yakin ingin menghapus:</p>
                <h4 class="fw-bold text-dark-emphasis" id="delete_nama"></h4>
                <p class="text-muted mt-3 small">Tindakan ini <strong>tidak dapat dibatalkan</strong>. Data absensi dan login pengguna ini akan dihapus permanen.</p>
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

@push('styles')
<style>
    .cursor-pointer { cursor: pointer; }
    .object-fit-cover { object-fit: cover; }

    /* Dark Mode Adaptations */
    .dark-mode .card { background-color: #1e1e1e !important; border-color: #333 !important; }
    .dark-mode .card-header { background-color: #252525 !important; border-bottom-color: #333 !important; color: #fff !important; }
    .dark-mode .card-body, .dark-mode .modal-content { background-color: #1e1e1e !important; color: #e0e0e0; }
    .dark-mode .modal-header { border-bottom-color: #444; }
    .dark-mode .modal-footer { background-color: #252525 !important; border-top-color: #444 !important; }
    .dark-mode .form-control, .dark-mode .form-select { background-color: #2b2b2b; border-color: #444; color: #fff; }
    .dark-mode .form-text { color: #aaa; }
    .dark-mode .table { color: #e0e0e0; border-color: #444; }
    .dark-mode .table-light th { background-color: #333 !important; color: #fff !important; border-color: #444 !important; }
    .dark-mode .table td { border-bottom-color: #333 !important; background-color: #1e1e1e !important; }
    .dark-mode .bg-light { background-color: #2b2b2b !important; }
    .dark-mode .text-dark-emphasis { color: #e0e0e0 !important; }
</style>
@endpush

@push('scripts')
<script>
    // Fungsi Toggle Password
    function togglePassword(id, el) {
        let input = document.getElementById(id);
        let icon = el.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // --- Logic Populate Modal Edit ---
        var editModal = document.getElementById('editKaryawanModal');
        if(editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                var btn = event.relatedTarget;

                // Set Action URL Form
                document.getElementById('editForm').action = '/admin/manajemen-karyawan/update/' + btn.getAttribute('data-id');

                // Isi Form Text
                document.getElementById('edit_nama').value = btn.getAttribute('data-nama');
                document.getElementById('edit_nip').value = btn.getAttribute('data-nip');
                document.getElementById('edit_departemen').value = btn.getAttribute('data-departemen');
                document.getElementById('edit_posisi').value = btn.getAttribute('data-posisi');
                document.getElementById('edit_username').value = btn.getAttribute('data-username');
                document.getElementById('edit_role').value = btn.getAttribute('data-role');
                document.getElementById('edit_telepon').value = btn.getAttribute('data-telepon');
                document.getElementById('edit_alamat').value = btn.getAttribute('data-alamat');

                // Reset Password Fields
                document.getElementById('editPass').value = '';
                document.getElementById('editPassConf').value = '';

                // Logic Preview Foto di Modal
                var fotoSrc = btn.getAttribute('data-foto');
                var imgPreview = document.getElementById('preview_foto');

                if(fotoSrc) {
                    imgPreview.src = fotoSrc;
                } else {
                    // Placeholder jika belum ada foto
                    imgPreview.src = 'https://ui-avatars.com/api/?name=' + btn.getAttribute('data-nama') + '&background=random';
                }
            });
        }

        // --- Logic Populate Modal Delete ---
        var deleteModal = document.getElementById('deleteKaryawanModal');
        if(deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                var btn = event.relatedTarget;
                document.getElementById('deleteForm').action = '/admin/manajemen-karyawan/destroy/' + btn.getAttribute('data-id');
                document.getElementById('delete_nama').textContent = btn.getAttribute('data-nama');
            });
        }
    });
</script>
@endpush