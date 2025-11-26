@extends('layouts.manajemen_app')

@section('page-title', 'Validasi & Approval')

@section('content')
<div class="row">
    <div class="col-12">

        <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="pills-absensi-tab" data-bs-toggle="pill" data-bs-target="#pills-absensi" type="button">
                    <i class="bi bi-camera-fill me-2"></i>Validasi Absensi
                    @if($pendingAbsensi->count() > 0) <span class="badge bg-danger ms-2">{{ $pendingAbsensi->count() }}</span> @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="pills-izin-tab" data-bs-toggle="pill" data-bs-target="#pills-izin" type="button">
                    <i class="bi bi-envelope-paper-fill me-2"></i>Approval Izin
                    @if($pendingIzin->count() > 0) <span class="badge bg-danger ms-2">{{ $pendingIzin->count() }}</span> @endif
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">

            <div class="tab-pane fade show active" id="pills-absensi">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Karyawan</th>
                                        <th>Tipe</th>
                                        <th>Foto</th>
                                        <th>Lokasi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingAbsensi as $att)
                                    <tr>
                                        <td>{{ $att->waktu_unggah->format('d M H:i') }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $att->employee->nama }}</div>
                                            <div class="small text-muted">{{ $att->employee->nip }}</div>
                                        </td>
                                        <td><span class="badge {{ $att->type == 'masuk' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($att->type) }}</span></td>
                                        <td>
                                            <img src="{{ asset($att->nama_file_foto) }}" class="rounded" width="50" style="cursor:pointer" data-bs-toggle="modal" data-bs-target="#img{{ $att->att_id }}">
                                            <div class="modal fade" id="img{{ $att->att_id }}"><div class="modal-dialog"><div class="modal-content"><img src="{{ asset($att->nama_file_foto) }}" class="w-100"></div></div></div>
                                        </td>
                                        <td>
                                            <a href="https://maps.google.com/?q={{ $att->latitude }},{{ $att->longitude }}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-geo-alt"></i></a>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <form action="{{ route('manajemen.validasi.submit') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="att_id" value="{{ $att->att_id }}">
                                                    <input type="hidden" name="status_validasi" value="Invalid">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tolak absensi ini?')"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#acc{{ $att->att_id }}"><i class="bi bi-check-lg"></i></button>
                                            </div>

                                            <div class="modal fade" id="acc{{ $att->att_id }}">
                                                <div class="modal-dialog">
                                                    <form action="{{ route('manajemen.validasi.submit') }}" method="POST">
                                                        @csrf
                                                        <div class="modal-content">
                                                            <div class="modal-header"><h6 class="modal-title">Validasi: {{ $att->employee->nama }}</h6></div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="att_id" value="{{ $att->att_id }}">
                                                                <input type="hidden" name="status_validasi" value="Valid">
                                                                <div class="mb-3">
                                                                    <label>Catatan</label>
                                                                    <input type="text" name="catatan_validasi" class="form-control" placeholder="Opsional">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer"><button type="submit" class="btn btn-success">Validasi</button></div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada antrean absensi.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pills-izin">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Karyawan</th>
                                        <th>Jenis</th>
                                        <th>Ket.</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingIzin as $leave)
                                    <tr>
                                        <td>{{ $leave->created_at->format('d M') }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $leave->employee->nama }}</div>
                                            <div class="small text-muted">{{ $leave->employee->nip }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $leave->tipe_izin == 'sakit' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ ucfirst($leave->tipe_izin) }}</span>
                                            @if($leave->file_bukti) <a href="{{ asset($leave->file_bukti) }}" target="_blank"><i class="bi bi-paperclip"></i></a> @endif
                                        </td>
                                        <td class="text-truncate" style="max-width: 150px;">{{ $leave->deskripsi }}</td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#izin{{ $leave->leave_id }}">Proses</button>

                                            <div class="modal fade" id="izin{{ $leave->leave_id }}">
                                                <div class="modal-dialog">
                                                    <form action="{{ route('manajemen.validasi.izin.submit') }}" method="POST">
                                                        @csrf
                                                        <div class="modal-content">
                                                            <div class="modal-header"><h6 class="modal-title">Approval Izin: {{ $leave->employee->nama }}</h6></div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="leave_id" value="{{ $leave->leave_id }}">
                                                                <div class="mb-3 bg-light p-2 rounded">{{ $leave->deskripsi }}</div>
                                                                <div class="mb-3">
                                                                    <label>Keputusan</label>
                                                                    <select name="status" class="form-select" required>
                                                                        <option value="disetujui">Setujui</option>
                                                                        <option value="ditolak">Tolak</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label>Catatan Admin</label>
                                                                    <textarea name="catatan_admin" class="form-control" rows="2"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada pengajuan izin.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    // Script JS untuk peta (sama seperti admin)
    document.addEventListener('DOMContentLoaded', function() {
        const delay = ms => new Promise(res => setTimeout(res, ms));
        const addressElements = document.querySelectorAll('.location-address');
        async function fetchAddresses() {
            for (let i = 0; i < addressElements.length; i++) {
                const el = addressElements[i];
                const lat = parseFloat(el.getAttribute('data-lat'));
                const lng = parseFloat(el.getAttribute('data-lng'));
                if (!lat || !lng || isNaN(lat) || isNaN(lng) || (Math.abs(lat) < 0.0001 && Math.abs(lng) < 0.0001)) {
                    el.innerHTML = '<span class="text-muted small">- Data lokasi kosong -</span>';
                    continue;
                }
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, { headers: { 'User-Agent': 'KlinikGrandWardenApp/1.0' } });
                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();
                    if (data && data.display_name) { el.innerHTML = `<i class="bi bi-pin-map-fill text-secondary me-1"></i> ${data.display_name}`; }
                    else { throw new Error('Alamat tidak ditemukan'); }
                } catch (error) { el.innerHTML = '<span class="text-muted text-decoration-underline" style="cursor:help" title="Server peta tidak merespon.">Gagal memuat alamat</span>'; }
                await delay(1200);
            }
        }
        if (addressElements.length > 0) { fetchAddresses(); }
    });
</script>
@endpush
