@extends('layouts.manajemen_app')

@section('page-title', 'Data Laporan Absensi')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>Rekapitulasi Absensi</h5>
            
            {{-- Tombol Export --}}
            <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i>Export CSV
            </button>
        </div>
        <div class="card-body">
            
            {{-- Filter Tanggal --}}
            <form action="{{ route('manajemen.laporan.index') }}" method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-filter me-2"></i>Tampilkan Data
                    </button>
                </div>
            </form>

            {{-- Tabel Data --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Karyawan</th>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Tipe</th>
                            <th>Status Validasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $att)
                            <tr>
                                <td class="fw-bold text-dark-emphasis">{{ $att->employee->nama ?? 'N/A' }}</td>
                                <td>{{ $att->waktu_unggah->format('d M Y') }}</td>
                                <td>{{ $att->waktu_unggah->format('H:i:s') }}</td>
                                <td>
                                    @if($att->type == 'masuk')
                                        <span class="badge bg-success bg-opacity-10 text-success">Masuk</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning">Pulang</span>
                                    @endif
                                </td>
                                <td>
                                    @if($att->validation && $att->validation->status_validasi_final == 'Valid')
                                        <span class="badge bg-primary">Valid</span>
                                    @elseif($att->validation && $att->validation->status_validasi_final == 'Invalid')
                                        <span class="badge bg-danger">Invalid</span>
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                    Tidak ada data absensi pada rentang tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Export (Reused logic) --}}
<div class="modal fade" id="exportModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Export Data Laporan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('manajemen.laporan.export') }}" method="POST">
          @csrf
          <div class="modal-body pt-4">
              <div class="mb-3">
                  <label class="form-label fw-bold small text-muted">Dari Tanggal</label>
                  <input type="date" name="start_date" class="form-control" required value="{{ request('start_date') }}">
              </div>
              <div class="mb-3">
                  <label class="form-label fw-bold small text-muted">Sampai Tanggal</label>
                  <input type="date" name="end_date" class="form-control" required value="{{ request('end_date') }}">
              </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success px-4 fw-bold">Download CSV</button>
          </div>
      </form>
    </div>
  </div>
</div>
@endsection