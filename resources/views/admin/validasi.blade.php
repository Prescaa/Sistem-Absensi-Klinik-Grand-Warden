@extends('layouts.admin_app')

@section('page-title', 'Validasi Absensi')

@section('content')
<div class="container-fluid">
    <h4 class="fw-bold mb-3">Data Absensi Menunggu Validasi</h4>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($attendances->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-check-all me-2"></i>
            Tidak ada data absensi yang perlu divalidasi saat ini.
        </div>
    @else
        <div class="row">
            @foreach($attendances as $att)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <a href="{{ $att->nama_file_foto }}" target="_blank">
                        <img src="{{ $att->nama_file_foto }}" class="card-img-top" alt="Foto Absensi" style="height: 300px; object-fit: cover;">
                    </a>
                    <div class="card-body">
                        <h5 class="card-title">{{ $att->employee->nama_lengkap ?? 'Karyawan' }}</h5>
                        <ul class="list-unstyled text-muted small">
                            <li><i class="bi bi-person-badge me-2"></i> NIK: {{ $att->employee->nik ?? 'N/A' }}</li>
                            <li><i class="bi bi-clock me-2"></i> Waktu: {{ $att->waktu_unggah->format('d M Y, H:i') }}</li>
                            <li><i class="bi bi-geo-alt me-2"></i> Lokasi: {{ $att->latitude }}, {{ $att->longitude }}</li>
                        </ul>

                        <form action="{{ route('admin.validasi.submit') }}" method="POST">
                            @csrf
                            <input type="hidden" name="att_id" value="{{ $att->att_id }}">

                            <div class="mb-2">
                                <label for="catatan-{{ $att->att_id }}" class="form-label small">Catatan (Opsional jika ditolak):</label>
                                <textarea id="catatan-{{ $att->att_id }}" name="catatan_validasi" class="form-control form-control-sm" rows="2"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="status_validasi" value="Approved" class="btn btn-success">
                                    <i class="bi bi-check-lg me-2"></i> Approve
                                </button>
                                <button type="submit" name="status_validasi" value="Rejected" class="btn btn-danger">
                                    <i class="bi bi-x-lg me-2"></i> Reject
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
