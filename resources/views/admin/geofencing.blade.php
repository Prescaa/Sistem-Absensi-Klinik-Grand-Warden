{{-- resources/views/admin/geofencing.blade.php --}}
@extends('layouts.admin_app')
@section('page-title', 'Pengaturan Lokasi Geofencing')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Formulir Pengaturan Lokasi</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.geofencing.save') }}" method="POST">
                        @csrf  {{-- Token Keamanan Laravel --}}

                        <div class="mb-3">
                            <label for="nama_area" class="form-label">Nama Area</label>
                            <input type="text" class="form-control" id="nama_area" name="nama_area"
                                   placeholder="Klinik Grand Warden"
                                   value="{{ $lokasi->nama_area ?? old('nama_area', 'Klinik Grand Warden') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude"
                                   placeholder="-6.123456"
                                   value="{{ $lokasi->latitude ?? old('latitude') }}" required>
                            <div class="form-text">Contoh: -6.208763</div>
                        </div>

                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude"
                                   placeholder="106.845678"
                                   value="{{ $lokasi->longitude ?? old('longitude') }}" required>
                            <div class="form-text">Contoh: 106.845599</div>
                        </div>

                        <div class="mb-3">
                            <label for="radius" class="form-label">Radius (dalam meter)</label>
                            <input type="number" class="form-control" id="radius" name="radius"
                                   placeholder="100"
                                   value="{{ $lokasi->radius_geofence ?? old('radius') }}" required>
                            <div class="form-text">Radius toleransi absensi (minimal 50m).</div>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save-fill me-2"></i> Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        </div>
</div>
@endsection
