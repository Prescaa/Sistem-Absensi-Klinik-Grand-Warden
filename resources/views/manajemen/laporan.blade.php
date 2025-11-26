@extends('layouts.manajemen_app')

@section('page-title', 'Data Laporan Absensi')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark-emphasis"><i class="bi bi-table me-2"></i>Rekapitulasi Absensi</h5>
            
            {{-- Tombol Export --}}
            <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i>Export CSV
            </button>
        </div>
        <div class="card-body">
            
            {{-- === FILTER & SORTING TOOLBAR === --}}
            <form action="{{ route('manajemen.laporan.index') }}" method="GET" class="row g-3 mb-4 p-3 bg-light rounded border filter-box">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Urutkan Data</label>
                    <select name="sort_by" class="form-select">
                        <option value="desc" {{ request('sort_by') == 'desc' ? 'selected' : '' }}>Terbaru (Desc)</option>
                        <option value="asc" {{ request('sort_by') == 'asc' ? 'selected' : '' }}>Terlama (Asc)</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-funnel-fill me-2"></i>Terapkan Filter
                    </button>
                </div>
            </form>

            {{-- Tabel Data --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle border mb-0">
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
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">Masuk</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Pulang</span>
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
                                    Tidak ada data absensi pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Export --}}
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

@push('styles')
<style>
    /* === CSS DARK MODE YANG DIPERBAIKI (KONTRAS TINGGI) === */
    
    /* 1. Warna Kartu: Lebih terang dari background body */
    .dark-mode .card {
        background-color: #1e1e1e !important;
        border: 1px solid #444 !important;
        color: #e0e0e0 !important;
    }
    
    /* 2. Header Kartu */
    .dark-mode .card-header {
        background-color: #2d2d2d !important;
        border-bottom: 1px solid #444 !important;
        color: #fff !important;
    }
    
    .dark-mode .card-header.bg-white {
        background-color: #2d2d2d !important;
    }
    
    /* 3. Filter Box (Container Input) */
    .dark-mode .filter-box {
        background-color: #2d2d2d !important;
        border: 1px solid #444 !important;
    }
    
    /* 4. Form Controls (Input & Select) */
    .dark-mode .form-control, 
    .dark-mode .form-select {
        background-color: #1e1e1e !important;
        border: 1px solid #555 !important;
        color: #fff !important;
    }
    
    .dark-mode .form-control:focus, 
    .dark-mode .form-select:focus {
        border-color: #0d6efd !important;
        background-color: #1e1e1e !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        color: #fff !important;
    }

    /* 5. Input Date Icon Fix */
    .dark-mode input[type="date"] {
        color-scheme: dark;
    }
    
    .dark-mode ::-webkit-calendar-picker-indicator {
        filter: invert(1) !important;
        opacity: 0.8;
        cursor: pointer;
    }

    /* 6. Labels dan Text */
    .dark-mode .text-muted { 
        color: #adb5bd !important; 
    }
    
    .dark-mode .text-dark-emphasis { 
        color: #f8f9fa !important; 
    }
    
    .dark-mode .fw-bold.text-dark-emphasis {
        color: #fff !important;
    }
    
    /* 7. Tabel */
    .dark-mode .table {
        border-color: #444 !important;
        color: #e0e0e0 !important;
    }
    
    .dark-mode .table-light th {
        background-color: #2d2d2d !important;
        border-color: #444 !important;
        color: #fff !important;
    }
    
    .dark-mode .table tbody td {
        background-color: #1e1e1e !important;
        border-bottom: 1px solid #444 !important;
        color: #e0e0e0 !important;
    }
    
    .dark-mode .table-hover tbody tr:hover td {
        background-color: #2d2d2d !important;
    }
    
    /* 8. Badges dengan Opacity (Perbaikan Khusus) */
    .dark-mode .badge.bg-success.bg-opacity-10 {
        background-color: rgba(25, 135, 84, 0.2) !important;
        color: #75b798 !important;
        border-color: #198754 !important;
    }
    
    .dark-mode .badge.bg-warning.bg-opacity-10 {
        background-color: rgba(255, 193, 7, 0.2) !important;
        color: #ffd965 !important;
        border-color: #ffc107 !important;
    }
    
    .dark-mode .badge.bg-primary {
        background-color: #0d6efd !important;
        color: #fff !important;
    }
    
    .dark-mode .badge.bg-danger {
        background-color: #dc3545 !important;
        color: #fff !important;
    }
    
    .dark-mode .badge.bg-secondary {
        background-color: #6c757d !important;
        color: #fff !important;
    }
    
    /* 9. Buttons */
    .dark-mode .btn-success {
        background-color: #198754 !important;
        border-color: #198754 !important;
    }
    
    .dark-mode .btn-primary {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    
    .dark-mode .btn-light {
        background-color: #3d3d3d !important;
        border-color: #555 !important;
        color: #fff !important;
    }
    
    .dark-mode .btn-light:hover {
        background-color: #4d4d4d !important;
        border-color: #666 !important;
    }
    
    /* 10. Modal */
    .dark-mode .modal-content {
        background-color: #1e1e1e !important;
        border: 1px solid #444 !important;
        color: #fff !important;
    }
    
    .dark-mode .modal-header {
        border-bottom: 1px solid #444 !important;
    }
    
    .dark-mode .modal-footer {
        border-top: 1px solid #444 !important;
    }
    
    .dark-mode .bg-light {
        background-color: #2d2d2d !important;
    }
    
    .dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
    
    /* 11. Icons dalam Tabel Kosong */
    .dark-mode .bi-inbox {
        color: #6c757d !important;
    }
    
    /* 12. Shadow Effects */
    .dark-mode .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.4) !important;
    }
    
    .dark-mode .shadow {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.5) !important;
    }
</style>
@endpush