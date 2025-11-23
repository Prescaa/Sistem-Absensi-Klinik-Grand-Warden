@extends('layouts.app')

@section('page-title', 'Pengajuan Izin')

@section('content')
<div class="container-fluid">

    {{-- Pesan Sukses/Error --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        {{-- KIRI: Formulir Pengajuan --}}
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Formulir Pengajuan</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('karyawan.izin.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="tipe_izin" class="form-label fw-semibold">Jenis Izin</label>
                            <select name="tipe_izin" id="tipe_izin" class="form-select" required>
                                <option value="" selected disabled>-- Pilih Jenis --</option>
                                <option value="sakit">Sakit (Perlu Surat Dokter)</option>
                                <option value="izin">Izin (Keperluan Pribadi)</option>
                                <option value="cuti">Cuti Tahunan</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="tanggal_mulai" class="form-label fw-semibold">Mulai Tanggal</label>
                                <input type="date" name="tanggal_mulai" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="tanggal_selesai" class="form-label fw-semibold">Sampai Tanggal</label>
                                <input type="date" name="tanggal_selesai" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label fw-semibold">Alasan / Keterangan</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan alasan pengajuan izin..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="file_bukti" class="form-label fw-semibold">Bukti Pendukung (Wajib diunggah jika sakit)</label>
                            <input type="file" name="file_bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text text-muted small">Format: JPG, PNG, PDF. Max: 2MB. (Wajib untuk Sakit)</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2 fw-bold">
                                <i class="bi bi-send me-2"></i>Kirim Pengajuan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- KANAN: Riwayat Pengajuan --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Riwayat Pengajuan</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Tanggal</th>
                                    <th>Tipe</th>
                                    <th>Keterangan</th>
                                    <th style="width: 25%;">Status & Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($riwayatIzin as $izin)
                                    <tr>
                                        <td class="ps-4">
                                            <small class="d-block fw-bold text-dark">
                                                {{ \Carbon\Carbon::parse($izin->tanggal_mulai)->format('d M Y') }}
                                            </small>
                                            <small class="text-muted">
                                                s/d {{ \Carbon\Carbon::parse($izin->tanggal_selesai)->format('d M Y') }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($izin->tipe_izin == 'sakit')
                                                <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1">Sakit</span>
                                            @elseif($izin->tipe_izin == 'cuti')
                                                <span class="badge bg-info bg-opacity-10 text-info px-2 py-1">Cuti</span>
                                            @else
                                                <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1">Izin</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="d-inline-block text-truncate" style="max-width: 150px;" title="{{ $izin->deskripsi }}">
                                                {{ $izin->deskripsi }}
                                            </span>
                                            @if($izin->file_bukti)
                                                <br><a href="{{ asset($izin->file_bukti) }}" target="_blank" class="small text-primary text-decoration-none"><i class="bi bi-paperclip"></i> Lihat Bukti</a>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- Badge Status --}}
                                            @if($izin->status == 'pending')
                                                <span class="badge bg-secondary mb-1">Menunggu</span>
                                            @elseif($izin->status == 'disetujui')
                                                <span class="badge bg-success mb-1">Disetujui</span>
                                            @else
                                                <span class="badge bg-danger mb-1">Ditolak</span>
                                            @endif

                                            {{-- MENAMPILKAN CATATAN ADMIN --}}
                                            @if(!empty($izin->catatan_admin))
                                                <div class="alert alert-light border p-2 mt-1 mb-0 small text-muted" style="font-size: 0.8rem; line-height: 1.2;">
                                                    <strong class="d-block text-dark"><i class="bi bi-info-circle me-1"></i>Admin:</strong>
                                                    {{ $izin->catatan_admin }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                            Belum ada riwayat pengajuan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* === CSS DARK MODE LENGKAP === */
    
    /* Card di Dark Mode */
    .dark-mode .card {
        background-color: #1e1e1e !important;
        border-color: #333 !important;
        color: #e0e0e0;
    }
    .dark-mode .card-header {
        background-color: #252525 !important;
        border-bottom-color: #333 !important;
        color: #fff !important;
    }
    .dark-mode .card-body {
        background-color: #1e1e1e !important;
    }

    /* Form Control (Input, Select, Textarea) di Dark Mode */
    .dark-mode .form-control,
    .dark-mode .form-select {
        background-color: #2b2b2b !important;
        border-color: #444 !important;
        color: #fff !important;
    }
    .dark-mode .form-control:focus,
    .dark-mode .form-select:focus {
        background-color: #333 !important;
        border-color: #0d6efd !important;
        color: #fff !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    /* Placeholder warna */
    .dark-mode .form-control::placeholder {
        color: #aaa;
    }
    
    /* File Input: Tombol 'Choose File' */
    .dark-mode input[type="file"]::file-selector-button {
        background-color: #444;
        color: #fff;
        border: 1px solid #666;
    }
    .dark-mode input[type="file"]::file-selector-button:hover {
        background-color: #555;
    }

    /* Icon Kalender di Input Date */
    .dark-mode ::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }

    /* Tabel di Dark Mode */
    .dark-mode .table {
        color: #e0e0e0;
        border-color: #444;
    }
    /* Header Tabel (thead) agar tidak putih */
    .dark-mode .bg-light, 
    .dark-mode .table thead th {
        background-color: #2b2b2b !important;
        color: #fff !important;
        border-color: #444 !important;
    }
    /* Hover baris tabel */
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
        color: #e0e0e0 !important;
    }
    
    /* Text Utilities untuk Dark Mode */
    .dark-mode .text-dark { color: #e0e0e0 !important; }
    .dark-mode .text-muted { color: #a0a0a0 !important; }
    
    /* Alert di dalam tabel (Catatan Admin) saat Dark Mode */
    .dark-mode .alert-light {
        background-color: #2b2b2b !important;
        border-color: #444 !important;
        color: #ccc !important;
    }
    .dark-mode .alert-light strong {
        color: #fff !important;
    }
    
    /* File Input Text Help */
    .dark-mode .form-text {
        color: #aaa !important;
    }
</style>
@endpush