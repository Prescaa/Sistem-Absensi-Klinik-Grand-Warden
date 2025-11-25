<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page-title', 'Manajemen Dashboard') - Klinik Grand Warden</title>

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script src="{{ mix('js/app.js') }}" defer></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { background-color: #F8F9FA; transition: background-color 0.3s ease; }
        .sidebar {
            width: 280px; background-color: #212529; color: #fff;
            position: fixed; top: 0; left: 0; height: 100vh; z-index: 1000; overflow-y: auto; overflow-x: hidden;
        }
        .sidebar .nav-pills { width: 100%; padding: 0 10px; }
        .sidebar .nav-link {
            color: #adb5bd; padding: .75rem 1rem; margin-bottom: 0.5rem; font-size: 0.95rem;
            transition: .3s; border-radius: 8px; width: 100%; box-sizing: border-box;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar .nav-link:hover { color: #fff; background-color: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { color: #fff; background-color: #0d6efd; box-shadow: 0 4px 6px rgba(13, 110, 253, 0.3); }
        .sidebar-footer { margin-top: auto; padding-bottom: 1rem; padding-left: 10px; padding-right: 10px; }

        .main-wrapper {
            margin-left: 280px; width: calc(100% - 280px); min-height: 100vh;
            display: flex; flex-direction: column; transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .topbar { background-color: #fff; border-bottom: 1px solid #dee2e6; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); }
        .main-footer {
            background-color: #e9ecef; color: #6c757d; border-top: 1px solid #dee2e6;
            padding: 1rem; margin-top: auto;
        }
        .notification-badge { font-size: 0.6rem; padding: 0.25em 0.4em; }

        /* === STYLE NOTIFIKASI === */
        .notif-item {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .notif-item:hover {
            background: #f8f9fa;
        }
        .notif-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notif-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; color: #333; }
        .notif-msg { font-size: 0.85rem; color: #666; line-height: 1.3; }
        .notif-time { font-size: 0.75rem; color: #999; margin-top: 4px; display: flex; align-items: center; gap: 4px; }
        .notif-empty { padding: 20px; font-size: .9rem; color:#6c757d; text-align: center; }

        /* === DARK MODE === */
        .dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        .dark-mode .topbar {
            background-color: #2d2d2d;
            border-bottom-color: #444;
            color: #fff;
        }
        .dark-mode .main-footer {
            background-color: #2d2d2d;
            border-top-color: #444;
            color: #aaa;
        }

        /* Dark Mode: Card */
        .dark-mode .card {
            background-color: #2d2d2d;
            border-color: #444;
            color: #fff;
        }
        .dark-mode .card-header, .dark-mode .card-footer {
            background-color: #333;
            border-color: #444;
            color: #fff;
        }

        /* Dark Mode: Dropdown & Notif */
        .dark-mode .dropdown-menu {
            background-color: #2d2d2d;
            border-color: #444;
        }
        .dark-mode .dropdown-item { color: #e0e0e0; }
        .dark-mode .dropdown-item:hover { background-color: #3a3a3a; }
        .dark-mode .dropdown-header { color: #fff; }
        .dark-mode .dropdown-divider { border-top-color: #444; }

        .dark-mode .notif-title { color: #fff; }
        .dark-mode .notif-msg { color: #ccc; }
        .dark-mode .notif-item:hover { background: #3a3a3a; }

        /* Dark Mode: Tabel & Form */
        .dark-mode .table { color: #e0e0e0; border-color: #444; }
        .dark-mode .table-light th { background-color: #333; color: #fff; border-color: #444; }
        .dark-mode .table-hover tbody tr:hover { background-color: #3a3a3a; color: #fff; }

        .dark-mode .form-control, .dark-mode .form-select {
            background-color: #2b2b2b;
            border-color: #444;
            color: #fff;
        }
        .dark-mode .form-control:focus {
            background-color: #333;
            color: #fff;
            border-color: #0d6efd;
        }
        .dark-mode .bg-light { background-color: #2b2b2b !important; }
        .dark-mode .text-muted { color: #adb5bd !important; }
        .dark-mode { background-color: #1a1a1a; color: #e0e0e0; }
        .dark-mode .topbar { background-color: #2d2d2d; border-bottom-color: #444; color: #fff; }
        .dark-mode .main-footer { background-color: #2d2d2d; border-top-color: #444; color: #aaa; }
        .dark-mode .card { background-color: #2d2d2d; border-color: #444; color: #fff; }
        .dark-mode .card-header, .dark-mode .card-footer { background-color: #333; border-color: #444; color: #fff; }
        .dark-mode .dropdown-menu { background-color: #2d2d2d; border-color: #444; }
        .dark-mode .dropdown-item { color: #e0e0e0; }
        .dark-mode .dropdown-item:hover { background-color: #3a3a3a; }
        .dark-mode .text-dark { color: #fff !important; }
    </style>

    @stack('styles')
</head>
<body>
<div class="d-flex">

    <nav class="sidebar d-flex flex-column pt-3">
        <div>
            <a href="/manajemen/dashboard" class="d-flex align-items-center mb-4 text-white text-decoration-none px-3">
                <i class="bi bi-graph-up-arrow fs-2 me-2"></i>
                <span class="fs-4 fw-bold">Manajemen</span>
            </a>

            <ul class="nav nav-pills flex-column">
                <li class="nav-item mb-1">
                    <a href="/manajemen/dashboard" class="nav-link {{ Request::is('manajemen/dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-fill me-2"></i> Dashboard & Analisis
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/manajemen/laporan" class="nav-link {{ Request::is('manajemen/laporan') ? 'active' : '' }}">
                        <i class="bi bi-table me-2"></i> Data Laporan
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('manajemen.absensi.unggah') ? 'active' : '' }}" href="{{ route('manajemen.absensi.unggah') }}">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i> Unggah Absensi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('manajemen.absensi.riwayat') ? 'active' : '' }}" href="{{ route('manajemen.absensi.riwayat') }}">
                        <i class="bi bi-clock-history me-2"></i> Riwayat Absensi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('manajemen.izin.show') ? 'active' : '' }}" href="{{ route('manajemen.izin.show') }}">
                        <i class="bi bi-calendar-check-fill me-2"></i> Ajukan Izin
                    </a>
                </li>
            </ul>


        </div>

        <div class="sidebar-footer mt-auto">
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="/manajemen/profil" class="nav-link {{ Request::is('manajemen/profil') ? 'active' : '' }}">
                        <i class="bi bi-person-circle me-2"></i> Profil Saya
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/logout" class="nav-link">
                        <i class="bi bi-box-arrow-left me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main-wrapper">

        <header class="topbar d-flex justify-content-between align-items-center p-3">
            <h4 class="fw-bold mb-0">@yield('page-title')</h4>

            <div class="d-flex align-items-center">

                <div class="position-relative me-3" style="cursor: pointer;">
                    <i class="bi bi-moon-fill fs-5 hover-primary dark-mode-toggle"></i>
                </div>

                <div class="dropdown me-3">
                    <div class="position-relative" id="adminNotifToggle" data-bs-toggle="dropdown" style="cursor:pointer;">
                        <i class="bi bi-bell-fill fs-5 hover-primary"></i>
                    </div>
                </div>

                @php
                    $user = Auth::user();
                    $employee = $user->employee;
                    $namaLengkap = $employee->nama ?? $user->username;
                    $initial = strtoupper(substr($namaLengkap, 0, 1));
                    $roleDisplay = ucfirst($user->role);
                    // ✅ Pastikan ambil foto terbaru langsung dari relasi DB
                    $fotoProfil = $employee->foto_profil ?? null;
                @endphp

                {{-- ✅ FIX: Header Profil Bisa Diklik & Update Foto Otomatis --}}
                <a href="{{ route('manajemen.profil') }}" class="d-flex align-items-center text-decoration-none text-dark profile-link">
                    <div class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center overflow-hidden border shadow-sm"
                         style="width:40px;height:40px;">

                        @if($fotoProfil)
                            {{-- Tambah timestamp agar cache browser refresh saat foto ganti --}}
                            <img src="{{ asset($fotoProfil) }}?v={{ time() }}" alt="Profil" class="w-100 h-100 object-fit-cover">
                        @else
                            <span style="font-weight:bold;font-size:1.2rem;">{{ $initial }}</span>
                        @endif
                    </div>

                    <div class="ms-2">
                        <span class="fw-bold d-block" style="line-height: 1.2;">{{ $namaLengkap }}</span>
                        <small class="text-muted">{{ $roleDisplay }}</small>
                    </div>
                </a>

            </div>
        </header>

        <main class="p-4 flex-grow-1">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>

        <footer class="main-footer d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <strong class="me-3">Klinik Grand Warden</strong>
                <span class="text-muted d-none d-md-inline">Jl. Medan Merdeka Timur No.11-13 Clash Universe</span>
            </div>

            <div class="d-flex">
                {{-- Facebook --}}
                <a href="https://www.facebook.com" target="_blank" class="text-decoration-none me-3">
                    <i class="bi bi-facebook fs-6 text-muted hover-primary"></i>
                </a>

                {{-- Twitter / X --}}
                <a href="https://twitter.com" target="_blank" class="text-decoration-none me-3">
                    <i class="bi bi-twitter-x fs-6 text-muted hover-primary"></i>
                </a>

                {{-- Instagram --}}
                <a href="https://www.instagram.com" target="_blank" class="text-decoration-none">
                    <i class="bi bi-instagram fs-6 text-muted hover-primary"></i>
                </a>

            </div>
        </footer>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const darkToggle = document.querySelector('.dark-mode-toggle');
        const body = document.body;

        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        if (isDarkMode) {
            body.classList.add('dark-mode');
            if(darkToggle) darkToggle.classList.replace('bi-moon-fill', 'bi-sun-fill');
        }

        if(darkToggle) {
            darkToggle.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                const isNowDark = body.classList.contains('dark-mode');
                this.classList.toggle('bi-moon-fill');
                this.classList.toggle('bi-sun-fill');
                localStorage.setItem('darkMode', isNowDark);
            });
        }
    });
</script>

@stack('scripts')
</body>
</html>
