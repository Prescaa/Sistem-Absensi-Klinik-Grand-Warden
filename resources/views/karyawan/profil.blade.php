@extends('layouts.app')

@section('page-title', 'Pengaturan Profil')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            <form action="#" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4 text-center d-flex flex-column align-items-center">
                        <img src="https://via.placeholder.com/200" class="rounded-circle bg-light mb-3" alt="Foto Profil">
                        <button type="button" class="btn btn-primary">
                            <i class="bi bi-camera-fill me-2"></i> Ganti Foto
                        </button>
                    </div>

                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" class="form-control" value="mahardika@kgh.com" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" value="Mahardika" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jabatan</label>
                            <input type="text" class="form-control" value="Head Department" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Departemen</label>
                            <input type="text" class="form-control" value="Logistic" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="alamatRumah" class="form-label">Alamat Rumah</label>
                            <input type="text" class="form-control" id="alamatRumah" value="Jl. Pasar Minggu, Jakarta Pusat">
                        </div>
                        <div class="mb-3">
                            <label for="nomorTelepon" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="nomorTelepon" value="+62 812-3456-7890">
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-danger">Batalkan</button>
                            <button type="submit" class="btn btn-warning">Simpan</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection