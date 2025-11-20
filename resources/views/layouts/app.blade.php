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
        /* === FLUID SCALING === */
        :root {
            font-size: clamp(14px, 0.9rem + 0.35vw, 20px);
            --sidebar-width: 17.5rem; 
            --topbar-height: 70px;
            --primary-color: #0d6efd; /* Warna Utama Biru */
        }

        body { 
            background-color: #F8F9FA;
            transition: background-color 0.3s ease;
            font-size: 1rem;
            overflow-x: hidden;
        }

        /* === SIDEBAR STYLE === */
        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background-color: #212529;
            color: #fff;
            transition: all 0.3s ease-in-out;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
        }

        /* === MAIN WRAPPER === */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease-in-out;
        }

        /* === TOGGLE BUTTON === */
        .sidebar-toggle {
            display: block;
            background: none;
            border: none;
            font-size: 1.5rem;
            margin-right: 1rem;
            cursor: pointer;
            color: #333;
            transition: color 0.3s;
        }

        /* === LOGIKA BUKA TUTUP === */
        @media (min-width: 993px) {
            body.sidebar-collapsed .sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            body.sidebar-collapsed .main-wrapper { margin-left: 0; width: 100%; }
        }
        @media (max-width: 992px) {
            .sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            .main-wrapper { margin-left: 0; width: 100%; }
            body.sidebar-open .sidebar { margin-left: 0; box-shadow: 5px 0 15px rgba(0,0,0,0.1); }
        }

        /* ... Style Navigasi ... */
        .sidebar .nav-link { color: #adb5bd; padding: .75rem 1.5rem; font-size: 0.95rem; transition: all 0.3s ease; }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active { color: #fff; background-color: var(--primary-color); border-radius: 8px; }
        .sidebar-footer { margin-top: auto; padding-bottom: 1rem; }

        .topbar {
            background-color: #fff;
            height: var(--topbar-height);
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
            transition: all 0.3s ease;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .main-footer {
            background-color: #e9ecef;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            margin-top: auto;
            transition: all 0.3s ease;
        }

        /* Dark Mode & Lainnya */
        .hover-primary:hover { color: var(--primary-color) !important; transition: color 0.3s ease; }
        .notification-badge { font-size: 0.6rem; padding: 0.25em 0.4em; }

        .dark-mode { background-color: #1a1a1a; color: #ffffff; }
        .dark-mode .sidebar { background-color: #1f1f1f; }
        .dark-mode .topbar { background-color: #2d2d2d; border-bottom-color: #444; color: #ffffff; }
        .dark-mode .main-footer { background-color: #2d2d2d; border-top-color: #444; color: #ffffff; }
        .dark-mode .sidebar-toggle { color: #fff; }
        
        /* Notifikasi Dropdown */
        .notification-dropdown {
            position: absolute; top: 100%; right: 0; width: 300px; background: white;
            border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000; display: none;
        }
        .dark-mode .notification-dropdown { background: #2d2d2d; border-color: #444; color: #ffffff; }
        .notification-item { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background-color 0.2s; }
        .dark-mode .notification-item { border-bottom-color: #444; }
        .notification-item:hover { background-color: #f8f9fa; }
        .dark-mode .notification-item:hover { background-color: #3d3d3d; }
        .notification-item:last-child { border-bottom: none; }
        .notification-header { padding: 12px 16px; border-bottom: 1px solid #dee2e6; font-weight: bold; }
        .dark-mode .notification-header { border-bottom-color: #444; }

        /* Avatar Style (PENTING UNTUK INISIAL) */
        .avatar-initial {
            width: 40px; height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    
    <nav class="sidebar d-flex flex-column p-3" id="sidebar">
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
        <div class="sidebar-footer">
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

    <div class="main-wrapper">
        <header class="topbar d-flex justify-content-between align-items-center p-3">
            <div class="d-flex align-items-center">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
                <h4 class="fw-bold mb-0">@yield('page-title')</h4> 
            </div>

            <div class="d-flex align-items-center">
                <div class="position-relative me-3" style="cursor: pointer;">
                    <i class="bi bi-moon-fill fs-5 hover-primary dark-mode-toggle"></i>
                </div>
                
                {{-- --- AWAL BAGIAN NOTIFIKASI --- --}}
                <div class="position-relative me-3">
                    <i class="bi bi-bell-fill fs-5 hover-primary notification-bell" style="cursor: pointer;"></i>
                    
                    {{-- Badge Merah Dinamis --}}
                    @if(isset($notifCount) && $notifCount > 0)
                        <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $notifCount }}
                        </span>
                    @endif
                    
                    {{-- Dropdown Isi Notifikasi --}}
                    <div class="notification-dropdown" style="width: 320px;">
                        <div class="notification-header d-flex justify-content-between align-items-center">
                            <span>Notifikasi</span>
                            @if(isset($notifCount) && $notifCount > 0)
                                <span class="badge bg-primary rounded-pill">{{ $notifCount }} Baru</span>
                            @endif
                        </div>

                        <div style="max-height: 300px; overflow-y: auto;">
                            @if(isset($globalNotifications) && count($globalNotifications) > 0)
                                @foreach($globalNotifications as $notif)
                                    {{-- Item Notifikasi --}}
                                    <a href="{{ $notif['url'] }}" class="text-decoration-none text-dark">
                                        <div class="notification-item">
                                            <div class="d-flex align-items-start">
                                                <div class="me-2 mt-1">
                                                    @if($notif['type'] == 'absensi')
                                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                                    @else
                                                        <i class="bi bi-info-circle-fill text-primary"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-bold small">{{ $notif['title'] }}</div>
                                                    <small class="text-muted d-block" style="line-height: 1.2;">
                                                        {{ $notif['message'] }}
                                                    </small>
                                                    <small class="text-secondary" style="font-size: 0.7rem;">
                                                        {{ \Carbon\Carbon::parse($notif['time'])->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            @else
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-bell-slash fs-4 mb-2 d-block"></i>
                                    <small>Tidak ada notifikasi baru</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- --- AKHIR BAGIAN NOTIFIKASI --- --}}
                
                @auth
                    @php
                        $user = Auth::user();
                        $employee = $user->employee;
                        $foto = $employee->foto_profil ?? null;
                        $name = $employee->nama ?? $user->username;
                        // Ambil inisial dari huruf pertama nama
                        $initial = strtoupper(substr($name, 0, 1));
                    @endphp
                    
                    <a href="/profil" class="d-flex align-items-center text-decoration-none text-dark">
                        @if($foto)
                            {{-- Jika ada foto di database --}}
                            <img src="{{ asset($foto) }}" class="rounded-circle shadow-sm" alt="Profil" style="width: 40px; height: 40px; object-fit: cover;">
                        @else
                            {{-- Jika tidak ada, tampilkan Inisial --}}
                            <div class="avatar-initial shadow-sm">
                                {{ $initial }}
                            </div>
                        @endif

                        <div class="ms-2 d-none d-sm-block text-start">
                            <span class="fw-bold d-block text-dark">{{ $name }}</span>
                            <small class="text-muted">{{ $user->email }}</small>
                        </div>
                    </a>
                @else
                    <div class="d-flex align-items-center">
                         <div class="avatar-initial shadow-sm">T</div>
                         <div class="ms-2"><span class="fw-bold">Tamu</span></div>
                    </div>
                @endauth
                </div>
        </header>

        <main class="p-4 flex-grow-1 overflow-auto">
            @yield('content')
        </main>

        <footer class="main-footer d-flex justify-content-between align-items-center p-3">
            <div class="d-flex align-items-center">
                <strong class="me-3">Klinik Grand Warden</strong>
                <span class="text-muted d-none d-md-inline">Jl. Medan Merdeka Timur No.11-13 Clash Universe</span>
            </div>
            <div class="d-flex">
                <i class="bi bi-facebook fs-6 me-3 text-muted hover-primary"></i>
                <i class="bi bi-twitter-x fs-6 me-3 text-muted hover-primary"></i>
                <i class="bi bi-instagram fs-6 text-muted hover-primary"></i>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebarToggle');
            const body = document.body;
            const sidebar = document.getElementById('sidebar');
            function isMobile() { return window.innerWidth <= 992; }

            if(toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (isMobile()) {
                        body.classList.toggle('sidebar-open');
                        body.classList.remove('sidebar-collapsed');
                    } else {
                        body.classList.toggle('sidebar-collapsed');
                        body.classList.remove('sidebar-open');
                    }
                });
            }
            document.addEventListener('click', function(e) {
                if (isMobile() && body.classList.contains('sidebar-open')) {
                    if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                        body.classList.remove('sidebar-open');
                    }
                }
            });

            const darkToggle = document.querySelector('.dark-mode-toggle');
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

            const notifBell = document.querySelector('.notification-bell');
            const notifDrop = document.querySelector('.notification-dropdown');
            const notifBadge = document.querySelector('.notification-badge');
            if(notifBell) {
                notifBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifDrop.style.display = notifDrop.style.display === 'block' ? 'none' : 'block';
                    if(notifBadge) notifBadge.style.display = 'none';
                });
            }
            document.addEventListener('click', function() {
                if(notifDrop) notifDrop.style.display = 'none';
            });
            if(notifDrop) notifDrop.addEventListener('click', e => e.stopPropagation());
        });
    </script>

    @stack('scripts')
</body>
</html>