{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin_app')
@section('page-title', 'Dashboard Admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">
                    <i class="bi bi-people-fill me-2"></i> Karyawan Terdaftar
                </div>
                <div class="card-body">
                    <h2 class="card-title fw-bold">{{ $totalKaryawan }}</h2>
                    <p class="card-text">Total karyawan di Klinik Grand Warden.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i> Validasi Pending
                </div>
                <div class="card-body">
                    <h2 class="card-title fw-bold">{{ $pendingValidasi }}</h2>
                    <p class="card-text">Absensi yang memerlukan validasi Anda.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">
                    <i class="bi bi-calendar-check-fill me-2"></i> Absensi Hari Ini
                </div>
                <div class="card-body">
                    <h2 class="card-title fw-bold">{{ $absensiHariIni }} / {{ $totalKaryawan }}</h2>
                    <p class="card-text">Total karyawan yang sudah absen hari ini.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Aktivitas Absensi Terbaru</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Nama Karyawan</th>
                        <th scope="col">Waktu Unggah</th>
                        <th scope="col">Tipe</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aktivitasTerbaru as $aktivitas)
                    <tr>
                        <td>
                            {{ $aktivitas->employee->nama ?? 'Karyawan Dihapus' }}
                        </td>
                        <td>
                            {{-- DIUBAH: Menampilkan 'waktu_unggah' --}}
                            {{ \Carbon\Carbon::parse($aktivitas->waktu_unggah)->format('d M Y, H:i') }}
                        </td>
                        <td>
                            @if($aktivitas->tipe_absensi == 'Masuk')
                                <span class="badge bg-success">{{ $aktivitas->tipe_absensi }}</span>
                            @else
                                <span class="badge bg-danger">{{ $aktivitas->tipe_absensi }}</span>
                            @endif
                        </td>
                        <td>
                            @if($aktivitas->validation)
                                <span class="badge {{ $aktivitas->validation->status_validasi == 'Approved' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $aktivitas->validation->status_validasi }}
                                </span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            Belum ada aktivitas absensi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
