@extends('layouts.app')

@section('page-title', 'Ajukan Izin')

@section('content')
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                            <i class="bi bi-person-fill text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted small mb-1">Nama Karyawan</h6>
                            <h5 class="fw-bold mb-0">Mahardika</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                            <i class="bi bi-clock-fill text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted small mb-1">Jam</h6>
                            <h5 class="fw-bold mb-0" id="current-time">--:--</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                            <i class="bi bi-calendar-fill text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted small mb-1">Tanggal</h6>
                            <h5 class="fw-bold mb-0" id="current-date">...</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                            <i class="bi bi-geo-alt-fill text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted small mb-1">Lokasi</h6>
                            <h5 class="fw-bold mb-0">Jl. Medan Merdeka T.</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            <form action="#" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tipeIzin" class="form-label fw-bold">Tipe Izin</label>
                        <select class="form-select" id="tipeIzin">
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="tanggalIzin" class="form-label fw-bold">Tanggal</label>
                        <input type="text" class="form-control" id="tanggalIzin" placeholder="DD/MM/YYYY">
                    </div>
                    <div class="col-md-4">
                        <label for="suratSakit" class="form-label fw-bold">Surat Sakit (Jika Sakit)</label>
                        <input class="form-control" type="file" id="suratSakit">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="alasanIzin" class="form-label fw-bold">Alasan</label>
                    <textarea class="form-control" id="alasanIzin" rows="5" placeholder="Tuliskan alasan Anda..."></textarea>
                </div>

                <div class="text-end mt-4"> <button type="submit" class="btn btn-primary btn-lg">Unggah</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .card {
        border-radius: 12px;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateDateTime() {
            const now = new Date();
            const timeEl = document.getElementById('current-time');
            const dateEl = document.getElementById('current-date');
            
            if (timeEl) {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                timeEl.textContent = `${hours}:${minutes}`;
            }
            
            if (dateEl) {
                const options = { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' };
                const dateStr = now.toLocaleDateString('id-ID', options).replace('.', ',');
                dateEl.textContent = dateStr;
            }
        }
        
        updateDateTime();
        setInterval(updateDateTime, 60000); // Update setiap menit
    });
</script>
@endpush