@extends('layouts.admin_app')
@section('page-title', 'Pengaturan Lokasi & Jam Kerja')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhpmA9ZinZ9Tvj+$/qrMUpS+P4cxVoPFgda8="
     crossorigin=""/>

    <style>
        #map {
            height: 500px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ddd;
            z-index: 1;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">

        {{-- KOLOM KIRI --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-sliders me-2"></i>Pengaturan Sistem</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.geofencing.save') }}" method="POST">
                        @csrf
                        
                        {{-- LOKASI --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                            <i class="bi bi-geo-alt-fill me-1"></i> Lokasi Kantor
                        </h6>
                        <div class="mb-3">
                            <label for="nama_area" class="form-label small fw-bold">Nama Area</label>
                            <input type="text" class="form-control" id="nama_area" name="nama_area"
                                   value="{{ $lokasi->nama_area ?? 'Klinik Grand Warden' }}" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Latitude</label>
                                <input type="text" class="form-control bg-light" id="latitude" name="latitude"
                                       value="{{ $lokasi->latitude ?? '' }}" required readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Longitude</label>
                                <input type="text" class="form-control bg-light" id="longitude" name="longitude"
                                       value="{{ $lokasi->longitude ?? '' }}" required readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="radius" class="form-label small fw-bold">Radius (Meter)</label>
                            <input type="number" class="form-control" id="radius" name="radius"
                                   value="{{ $lokasi->radius_geofence ?? 100 }}" required>
                        </div>

                        <hr class="my-4">

                        {{-- JAM KERJA --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                            <i class="bi bi-clock-fill me-1"></i> Jam Kerja Operasional
                        </h6>
                        
                        @php
                            $jamKerja = $lokasi->jam_kerja ?? [];
                            $jamMasuk = $jamKerja['masuk'] ?? '08:00';
                            $jamPulang = $jamKerja['pulang'] ?? '17:00';
                            $hariKerja = $jamKerja['hari_kerja'] ?? [1, 2, 3, 4, 5];
                        @endphp

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Masuk (Max)</label>
                                <input type="time" class="form-control" id="jam_masuk" name="jam_masuk"
                                       value="{{ $jamMasuk }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Pulang (Min)</label>
                                <input type="time" class="form-control" id="jam_pulang" name="jam_pulang"
                                       value="{{ $jamPulang }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold d-block">Hari Kerja Efektif</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach([1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 0 => 'Min'] as $key => $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="hari_kerja[]" 
                                               value="{{ $key }}" id="day_{{ $key }}"
                                               {{ in_array($key, $hariKerja) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="day_{{ $key }}">{{ $day }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text small">Hari tidak dicentang = Libur (Tidak wajib absen).</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                            <i class="bi bi-save-fill me-2"></i> Simpan Semua Pengaturan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-map-fill me-2"></i>Peta Geofencing</h5>
                </div>
                <div class="card-body p-0">
                    <div id="map"></div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const radiusInput = document.getElementById('radius');

            const initialLat = parseFloat(latInput.value) || -6.208763;
            const initialLng = parseFloat(lngInput.value) || 106.845599;
            const initialRadius = parseFloat(radiusInput.value) || 100;
            const initialCenter = [initialLat, initialLng];

            // Fallback value jika kosong
            if(!latInput.value) latInput.value = initialLat;
            if(!lngInput.value) lngInput.value = initialLng;

            const map = L.map('map').setView(initialCenter, 17);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const marker = L.marker(initialCenter, { draggable: true }).addTo(map);
            const circle = L.circle(initialCenter, {
                color: 'blue',
                fillColor: '#30f',
                fillOpacity: 0.2,
                radius: initialRadius
            }).addTo(map);

            function updateFormInputs(latlng) {
                latInput.value = latlng.lat.toFixed(6);
                lngInput.value = latlng.lng.toFixed(6);
            }

            marker.on('dragend', function (e) {
                const newPos = marker.getLatLng();
                circle.setLatLng(newPos);
                updateFormInputs(newPos);
            });

            radiusInput.addEventListener('input', function () {
                let newRadius = parseFloat(radiusInput.value) || 50;
                if (newRadius < 10) newRadius = 10;
                circle.setRadius(newRadius);
            });

            map.on('click', function (e) {
                const newPos = e.latlng;
                marker.setLatLng(newPos);
                circle.setLatLng(newPos);
                updateFormInputs(newPos);
            });
        });
    </script>
@endpush