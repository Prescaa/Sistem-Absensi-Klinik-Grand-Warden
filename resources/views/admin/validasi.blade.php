{{-- Menggunakan layout admin --}}
@extends('layouts.admin_app')

{{-- Mengatur judul halaman --}}
@section('page-title', 'Validasi Absensi')

{{-- Konten utama halaman validasi --}}
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Absensi Menunggu Validasi</h5>

            <!-- Nanti bisa ditambahkan filter berdasarkan tanggal atau karyawan -->
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Filter Status
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">Semua</a></li>
                    <li><a class="dropdown-item" href="#">Pending</a></li>
                    <li><a class="dropdown-item" href="#">Approved</a></li>
                    <li><a class="dropdown-item" href="#">Rejected</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Karyawan</th>
                            <th scope="col">Waktu Unggah</th>
                            <th scope="col">Foto</th>
                            <th scope="col">Lokasi (Lat/Lng)</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{--
                          Nanti, data ini akan di-loop dari controller
                          Contoh: @foreach ($attendances as $att) ... @endforeach
                        --}}

                        <!-- Contoh Data 1 (Pending) -->
                        <tr>
                            <th scope="row">1</th>
                            <td>
                                <div>Rifky Putra Mahardika</div>
                                <small class="text-muted">NIP: 2310817210023</small>
                            </td>
                            <td>15 Nov 2025 - 08:01:12</td>
                            <td>
                                <!-- Nanti link ini akan mengarah ke file foto -->
                                <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#fotoModal1">
                                    <i class="bi bi-eye-fill"></i> Lihat
                                </a>
                            </td>
                            <td>-3.3168, 114.5901</td>
                            <td class="text-center">
                                <!-- Form untuk Approve -->
                                <form action="/admin/validasi/approve" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menyetujui absensi ini?');">
                                    @csrf
                                    <input type="hidden" name="attendance_id" value="1">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>

                                <!-- Tombol untuk Reject (memicu modal) -->
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal1">
                                    <i class="bi bi-x-lg"></i> Reject
                                </button>
                            </td>
                        </tr>

                        <!-- Contoh Data 2 (Pending) -->
                        <tr>
                            <th scope="row">2</th>
                            <td>
                                <div>Alysa Armelia</div>
                                <small class="text-muted">NIP: 2310817120009</small>
                            </td>
                            <td>15 Nov 2025 - 08:03:45</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#fotoModal2">
                                    <i class="bi bi-eye-fill"></i> Lihat
                                </a>
                            </td>
                            <td>-3.3170, 114.5903</td>
                            <td class="text-center">
                                <form action="/admin/validasi/approve" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menyetujui absensi ini?');">
                                    @csrf
                                    <input type="hidden" name="attendance_id" value="2">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal2">
                                    <i class="bi bi-x-lg"></i> Reject
                                </button>
                            </td>
                        </tr>

                        <!-- Akhir Contoh Data -->
                    </tbody>
                </table>
            </div>

            <!-- Jika tidak ada data -->
            {{--
            <div class="text-center p-5">
                <i class="bi bi-check-all fs-1 text-success"></i>
                <h5 class="mt-3">Tidak ada absensi yang perlu divalidasi</h5>
                <p class="text-muted">Semua data absensi sudah tervalidasi.</p>
            </div>
            --}}
        </div>
    </div>
</div>

<!-- === MODAL UNTUK REJECT === -->

<!-- Modal untuk Data 1 -->
<div class="modal fade" id="rejectModal1" tabindex="-1" aria-labelledby="rejectModalLabel1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="rejectModalLabel1">Tolak Absensi: Rifky P.M.</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/admin/validasi/reject" method="POST">
          @csrf
          <div class="modal-body">
                <input type="hidden" name="attendance_id" value="1">
                <div class="mb-3">
                    <label for="catatan1" class="form-label">Alasan Penolakan:</label>
                    <textarea class="form-control" id="catatan1" name="catatan" rows="3" placeholder="Contoh: Foto buram, di luar area, dll." required></textarea>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger">Tolak Absensi</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal untuk Data 2 -->
<div class="modal fade" id="rejectModal2" tabindex="-1" aria-labelledby="rejectModalLabel2" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="rejectModalLabel2">Tolak Absensi: Alysa A.</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/admin/validasi/reject" method="POST">
          @csrf
          <div class="modal-body">
                <input type="hidden" name="attendance_id" value="2">
                <div class="mb-3">
                    <label for="catatan2" class="form-label">Alasan Penolakan:</label>
                    <textarea class="form-control" id="catatan2" name="catatan" rows="3" placeholder="Contoh: Foto buram, di luar area, dll." required></textarea>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger">Tolak Absensi</button>
          </div>
      </form>
    </div>
  </div>
</div>


<!-- === MODAL UNTUK LIHAT FOTO (Contoh) === -->
<div class="modal fade" id="fotoModal1" tabindex="-1" aria-labelledby="fotoModalLabel1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="fotoModalLabel1">Foto: Rifky P.M.</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
          <img src="https://placehold.co/400x400/000000/FFF?text=Foto+Absen" alt="Foto Absensi" class="img-fluid rounded">
          <p class="text-muted mt-2">15 Nov 2025 - 08:01:12</p>
      </div>
    </div>
  </div>
</div>
@endsection
