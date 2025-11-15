<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page-title') - Klinik Grand Warden</title>
    
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script src="{{ mix('js/app.js') }}" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { 
            background-color: #F8F9FA;
            transition: background-color 0.3s ease;
        }
        .sidebar {
            width: 280px;
            background-color: #212529;
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
            background-color: #0d6efd;
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

        /* Dark Mode Styles */
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

        /* Dropdown Notification */
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 300px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
        .dark-mode .notification-dropdown {
            background: #2d2d2d;
            border-color: #444;
            color: #ffffff;
        }
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .dark-mode .notification-item {
            border-bottom-color: #444;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .dark-mode .notification-item:hover {
            background-color: #3d3d3d;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-header {
            padding: 12px 16px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
        }
        .dark-mode .notification-header {
            border-bottom-color: #444;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex vh-100">
        <nav class="sidebar vh-100 d-flex flex-column p-3">
            <div>
                <a href="/dashboard" class="d-flex align-items-center mb-3 text-white text-decoration-none">
                    <i class="bi bi-heart-pulse-fill fs-2 me-2"></i>
                    <span class="fs-4">Klinik Grand Warden</span>
                </a>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item mb-1">
                        <a href="/dashboard" class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}">
                            <i class="bi bi-grid-fill me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/unggah" class="nav-link {{ Request::is('unggah') ? 'active' : '' }}">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Unggah Absensi
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/riwayat" class="nav-link {{ Request::is('riwayat') ? 'active' : '' }}">
                            <i class="bi bi-clock-history me-2"></i> Riwayat Absensi
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/izin" class="nav-link {{ Request::is('izin') ? 'active' : '' }}">
                            <i class="bi bi-calendar-check-fill me-2"></i> Ajukan Izin
                        </a>
                    </li>
                </ul>
            </div>
            <div class="sidebar-footer mt-auto">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item mb-1">
                        <a href="/profil" class="nav-link {{ Request::is('profil') ? 'active' : '' }}">
                            <i class="bi bi-person-circle me-2"></i> Pengaturan Profil
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="/logout" class="nav-link">
                            <i class="bi bi-box-arrow-left me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="w-100 d-flex flex-column" style="max-height: 100vh;">
            <header class="topbar d-flex justify-content-between align-items-center p-3">
                <h4 class="fw-bold mb-0">@yield('page-title')</h4> 
                <div class="d-flex align-items-center">
                    <div class="position-relative me-3" style="cursor: pointer;">
                        <i class="bi bi-moon-fill fs-5 hover-primary dark-mode-toggle"></i>
                    </div>
                    
                    <div class="position-relative me-3">
                        <i class="bi bi-bell-fill fs-5 hover-primary notification-bell" style="cursor: pointer;"></i>
                        <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
                        
                        <div class="notification-dropdown">
                            <div class="notification-header">
                                Notifikasi (3)
                            </div>
                            <div class="notification-item">
                                <div class="fw-bold">Absensi Menunggu Verifikasi</div>
                                <small class="text-muted">11 April 2025 - 08:15</small>
                            </div>
                            <div class="notification-item">
                                <div class="fw-bold">Izin Cuti Disetujui</div>
                                <small class="text-muted">19-28 Januari 2025</small>
                            </div>
                            <div class="notification-item">
                                <div class="fw-bold">Pengumuman Sistem</div>
                                <small class="text-muted">Update maintenance minggu depan</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <img src="https://via.placeholder.com/40" class="rounded-circle" alt="Profil" style="width: 40px; height: 40px;">
                        <div class="ms-2">
                            <span class="fw-bold d-block">
                                @auth
                                    {{ Auth::user()->employee->nama ?? Auth::user()->username }}
                                @else
                                    Nama Tamu
                                @endauth
                            </span>
                            
                            <small class="text-muted">
                                @auth
                                    {{ Auth::user()->email }}
                                @else
                                    email@tamu.com
                                @endauth
                            </small>
                        </div>
                    </div>
                    </div>
            </header>

            <main class="p-4 flex-grow-1 overflow-auto">
                @yield('content')
            </main>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dark Mode Functionality
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

            // Notification Dropdown
            const notificationBell = document.querySelector('.notification-bell');
            const notificationDropdown = document.querySelector('.notification-dropdown');
            const notificationBadge = document.querySelector('.notification-badge');
            
            if (notificationBell) {
                notificationBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.style.display = 
                        notificationDropdown.style.display === 'block' ? 'none' : 'block';
                    
                    if(notificationBadge) {
                        notificationBadge.style.display = 'none';
                    }
                });
            }

            document.addEventListener('click', function() {
                if(notificationDropdown) {
                    notificationDropdown.style.display = 'none';
                }
            });

            if(notificationDropdown) {
                notificationDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
    </SCriPt>

    @stack('scripts')
</body>
</html>