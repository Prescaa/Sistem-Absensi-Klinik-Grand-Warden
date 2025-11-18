{{-- resources/views/admin/geofencing.blade.php --}}
@extends('layouts.admin_app')
@section('page-title', 'Pengaturan Lokasi Geofencing')

@push('styles')
    {{-- Memuat CSS Leaflet.js --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhpmA9ZinZ9Tvj+$/qrMUpS+P4cxVoPFgda8="
     crossorigin=""/>

    <style>
        #map {
            height: 500px; /* Anda bisa sesuaikan tingginya di sini */
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ddd;
            z-index: 1; /* Penting agar peta tampil di atas elemen lain jika ada */
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">

        {{-- =================================== --}}
        {{-- KOLOM KIRI: FORMULIR PENGATURAN     --}}
        {{-- =================================== --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Formulir Pengaturan Lokasi</h5>
                </div>
                <div class="card-body">
                    {{-- Form tetap sama, backend tidak perlu diubah --}}
                    <form action="{{ route('admin.geofencing.save') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="nama_area" class="form-label">Nama Area</label>
                            <input type="text" class="form-control" id="nama_area" name="nama_area"
                                   placeholder="Klinik Grand Warden"
                                   value="{{ $lokasi->nama_area ?? old('nama_area', 'Klinik Grand Warden') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            {{-- Dibuat readonly karena diisi oleh peta --}}
                            <input type="text" class="form-control" id="latitude" name="latitude"
                                   placeholder="Klik di peta..."
                                   value="{{ $lokasi->latitude ?? old('latitude') }}" required readonly>
                            <div class="form-text">Akan terisi otomatis dari peta.</div>
                        </div>

                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            {{-- Dibuat readonly karena diisi oleh peta --}}
                            <input type="text" class="form-control" id="longitude" name="longitude"
                                   placeholder="Klik di peta..."
                                   value="{{ $lokasi->longitude ?? old('longitude') }}" required readonly>
                            <div class="form-text">Akan terisi otomatis dari peta.</div>
                        </div>

                        <div class="mb-3">
                            <label for="radius" class="form-label">Radius (dalam meter)</label>
                            {{-- Readonly dihapus agar bisa diedit --}}
                            <input type="number" class="form-control" id="radius" name="radius"
                                   placeholder="100"
                                   value="{{ $lokasi->radius_geofence ?? old('radius', 100) }}" required>
                            <div class="form-text">Minimal 50m. Ubah nilai ini untuk melihat visualisasi di peta.</div>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save-fill me-2"></i> Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- =================================== --}}
        {{-- KOLOM KANAN: VISUALISASI PETA        --}}
        {{-- =================================== --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Visualisasi Peta</h5>
                </div>
                <div class="card-body p-3">
                    {{-- Div untuk Peta Leaflet --}}
                    <div id="map"></div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
    {{-- Memuat JavaScript Leaflet.js --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>

    {{-- Script untuk inisialisasi peta (TETAP SAMA, tidak perlu diubah) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- 1. AMBIL ELEMEN FORM ---
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const radiusInput = document.getElementById('radius');

            // --- 2. TENTUKAN LOKASI AWAL ---
            const initialLat = parseFloat(latInput.value) || -6.208763;
            const initialLng = parseFloat(lngInput.value) || 106.845599;
            const initialRadius = parseFloat(radiusInput.value) || 100;
            const initialCenter = [initialLat, initialLng];

            // --- 3. INISIALISASI PETA ---
            const map = L.map('map').setView(initialCenter, 17); // [lat, lng], zoom

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // --- 4. BUAT MARKER ---
            const marker = L.marker(initialCenter, {
                draggable: true // Marker bisa digeser
            }).addTo(map);

            // --- 5. BUAT LINGKARAN (RADIUS) ---
            const circle = L.circle(initialCenter, {
                color: 'red',
                fillColor: '#f03',
                fillOpacity: 0.2,
                radius: initialRadius // Radius awal
            }).addTo(map);

            // --- 6. SINKRONISASI DATA ---
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
                if (newRadius < 50) newRadius = 50;
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
