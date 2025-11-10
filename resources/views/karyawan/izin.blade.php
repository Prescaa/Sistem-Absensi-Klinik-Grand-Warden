@extends('layouts.app')

@section('page-title', 'Ajukan Izin')

@section('content')
    <div class="row mb-4">
        </div>

    <div class="card shadow-sm border-0">
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

                <div classs="text-end mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Unggah</button>
                </div>
            </form>
        </div>
    </div>
@endsection