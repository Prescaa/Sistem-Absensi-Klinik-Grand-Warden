<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Judul halaman akan dinamis, defaultnya "Admin - Klinik Grand Warden" -->
    <title>@yield('page-title', 'Admin Dashboard') - Admin - Klinik Grand Warden</title>

    <!-- Memuat aset CSS dan JS utama -->
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script src="{{ mix('js/app.js') }}" defer></script>

    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Style ini disalin dari layouts/app.blade.php untuk konsistensi -->
    <style>
        body {
            background-color: #F8F9FA;
            transition: background-color 0.3s ease;
        }
        .sidebar {
            width: 280px;
            background-color: #212529; /* Warna gelap untuk sidebar admin */
            color: #fff;
            transition: background-color 0.3s ease;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            padding: .75rem 1.5rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd; /* Warna biru untuk link aktif */
            border-radius: 8px;
        }
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 280px;
        }
        .topbar {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
            transition: all 0.3s ease;
        }
        .main-footer {
            background-color: #e9ecef;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            margin-top: auto;
            transition: all 0.3s ease;
        }
        .hover-primary:hover {
            color: #0d6efd !important;
            transition: color 0.3s ease;
        }
        .notification-badge {
            font-size: 0.6rem;
            padding: 0.25em 0.4em;
        }

        /* Style Dark Mode */
        .dark-mode {
            background-color: #1a1a1a;
            color: #ffffff;
        }
        .dark-mode .topbar {
            background-color: #2d2d2d;
            border-bottom-color: #444;
            color: #ffffff;
        }
        .dark-mode .main-footer {
            background-color: #2d2d2d;
            border-top-color: #444;
            color: #ffffff;
        }
        .dark-mode .card {
            background-color: #2d2d2d;
            border-color: #444;
            color: #ffffff;
        }
        .dark-mode .text-muted {
            color: #adb5bd !important;
        }
    </style>
    <!-- Untuk style tambahan per halaman -->
    @stack('styles')
</head>
<body>
    <div class="d-flex vh-100">

        <!-- === Sidebar Admin === -->
        <nav class="sidebar vh-100 d-flex flex-column p-3">
            <div>
                <!-- Judul Sidebar -->
                <a href="/admin/dashboard" class="d-flex align-items-center mb-3 text-white text-decoration-none">
                    <i class="bi bi-shield-lock-fill fs-2 me-2"></i>
                    <span class="fs-4">Admin Klinik</span>
                </a>

                <!-- Link Navigasi Admin -->
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item mb-1">
                        <a href="/admin/dashboard" class="nav-link {{ Request::is('admin/dashboard') ? 'active' : '' }}">
                            <i class="bi bi-grid-fill me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/admin/validasi" class="nav-link {{ Request::is('admin/validasi') ? 'active' : '' }}">
                            <i class="bi bi-check-circle-fill me-2"></i> Validasi Absensi
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/admin/manajemen-karyawan" class="nav-link {{ Request::is('admin/manajemen-karyawan') ? 'active' : '' }}">
                            <i class="bi bi-people-fill me-2"></i> Manajemen Karyawan
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/admin/geofencing" class="nav-link {{ Request::is('admin/geofencing') ? 'active' : '' }}">
                            <i class="bi bi-geo-alt-fill me-2"></i> Lokasi Geofencing
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Footer Sidebar (Logout) -->
            <div class="sidebar-footer mt-auto">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item mb-1">
                        <a href="/logout" class="nav-link">
                            <i class="bi bi-box-arrow-left me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- === Area Konten Utama === -->
        <div class="w-100 d-flex flex-column">

            <!-- Header -->
            <header class="topbar d-flex justify-content-between align-items-center p-3">
                <!-- Judul halaman diambil dari @yield('page-title') -->
                <h4 class="fw-bold mb-0">@yield('page-title')</h4>

                <div class="d-flex align-items-center">
                    <!-- Tombol Dark Mode -->
                    <div class="position-relative me-3" style="cursor: pointer;">
                        <i class="bi bi-moon-fill fs-5 hover-primary dark-mode-toggle"></i>
                    </div>

                    <!-- Lonceng Notifikasi -->
                    <div class="position-relative me-3">
                        <i class="bi bi-bell-fill fs-5 hover-primary notification-bell" style="cursor: pointer;"></i>
                        <!-- Badge ini bisa diisi data dinamis, misal jumlah validasi pending -->
                        <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            5 <!-- Contoh Angka -->
                        </span>
                    </div>

                    <!-- Profil Admin -->
                    <div class="d-flex align-items-center">
                        <img src="https://via.placeholder.com/40" class="rounded-circle" alt="Profil" style="width: 40px; height: 40px;">
                        <div class="ms-2">
                            <!-- Mengambil nama admin yang sedang login -->
                            <span class="fw-bold d-block">{{ Auth::user()->username ?? 'Admin' }}</span>
                            <small class="text-muted">Administrator</small>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Konten Halaman -->
            <main class="p-4 flex-grow-1 overflow-auto">
                <!-- Notifikasi (jika ada) -->
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Konten dari file (dashboard, validasi, dll) akan dimuat di sini -->
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="main-footer d-flex justify-content-between align-items-center p-3">
                <div class="d-flex align-items-center">
                    <strong class="me-3">Klinik Grand Warden</strong>
                    <span class="text-muted">Jl. Medan Merdeka Timur No.11-13 Clash Universe</span>
                </div>
                <div class="d-flex">
                    <i class="bi bi-facebook fs-6 me-3 text-muted hover-primary"></i>
                    <i class="bi bi-twitter-x fs-6 me-3 text-muted hover-primary"></i>
                    <i class="bi bi-instagram fs-6 text-muted hover-primary"></i>
                </div>
            </footer>
        </div>
    </div>

    <!-- Script untuk Dark Mode (disalin dari app.blade.php) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const darkToggle = document.querySelector('.dark-mode-toggle');
            const body = document.body;

            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                body.classList.add('dark-mode');
                darkToggle.classList.remove('bi-moon-fill');
                darkToggle.classList.add('bi-sun-fill');
            }

            darkToggle.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                const isNowDark = body.classList.contains('dark-mode');

                this.classList.toggle('bi-moon-fill');
                this.classList.toggle('bi-sun-fill');
                localStorage.setItem('darkMode', isNowDark);
            });

            // Script notifikasi bisa ditambahkan di sini
        });
    </script>

    <!-- Untuk script tambahan per halaman -->
    @stack('scripts')
</body>
</html>
