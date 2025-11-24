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
        /* === SETTING UKURAN (FIXED/ADMIN STYLE) === */
        :root {
            --sidebar-width: 280px; 
            --topbar-height: 70px;
            --primary-color: #0d6efd; 
        }

        body { 
            background-color: #F8F9FA;
            transition: background-color 0.3s ease;
            font-size: 0.925rem;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            overflow-x: hidden;
        }

        /* === SIDEBAR STYLE === */
        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background-color: #212529;
            color: #fff;
            transition: margin-left 0.3s ease;
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
            transition: margin-left 0.3s ease, width 0.3s ease;
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

        /* === LOGIKA BUKA TUTUP (RESPONSIF) === */
        @media (min-width: 993px) {
            body.sidebar-collapsed .sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            body.sidebar-collapsed .main-wrapper { margin-left: 0; width: 100%; }
        }

        @media (max-width: 992px) {
            .sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            .main-wrapper { margin-left: 0; width: 100%; }
            body.sidebar-open .sidebar { margin-left: 0; box-shadow: 5px 0 15px rgba(0,0,0,0.2); }
        }

        .sidebar .nav-link { color: #adb5bd; padding: 0.75rem 1.5rem; font-size: 0.95rem; transition: all 0.2s; }
        .sidebar .nav-link:hover { color: #fff; background-color: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { color: #fff; background-color: var(--primary-color); border-radius: 0.25rem; }
        .sidebar-footer { margin-top: auto; padding-bottom: 1rem; }

        /* === TOPBAR & FOOTER === */
        .topbar {
            background-color: #fff;
            height: var(--topbar-height);
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: sticky; top: 0; z-index: 999;
        }
        
        .main-footer {
            background-color: #e9ecef;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            margin-top: auto;
            transition: all 0.3s ease;
        }

        /* === DARK MODE STYLES === */
        .hover-primary:hover { color: var(--primary-color) !important; transition: color 0.3s ease; }
        
        /* Badge diset default display none, JS yang akan nyalakan */
        .notification-badge { 
            font-size: 0.6rem; 
            padding: 0.25em 0.4em; 
            display: none; 
        }

        .dark-mode { background-color: #121212 !important; color: #e0e0e0; }
        .dark-mode .sidebar { background-color: #1f1f1f; border-right: 1px solid #333; }
        .dark-mode .topbar { background-color: #1e1e1e !important; border-bottom-color: #333 !important; color: #fff; }
        .dark-mode .main-footer { background-color: #1e1e1e !important; border-top-color: #333 !important; color: #aaa; }
        .dark-mode .sidebar-toggle { color: #fff !important; }
        .dark-mode .card { background-color: #1e1e1e !important; border-color: #333 !important; color: #fff; }
        .dark-mode .bg-white { background-color: #1e1e1e !important; color: #fff !important; }
        .dark-mode .bg-light { background-color: #2b2b2b !important; color: #fff !important; }
        .dark-mode .text-dark { color: #fff !important; }
        .dark-mode .text-muted { color: #aaa !important; }
        .dark-mode .border { border-color: #444 !important; }
        
        .notification-dropdown {
            position: absolute; top: 100%; right: 0; width: 300px; background: white;
            border: 1px solid #dee2e6; border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
                <h4 class="fw-bold mb-0 text-dark">@yield('page-title')</h4> 
            </div>

            <div class="d-flex align-items-center">
                
                <div class="position-relative me-3" style="cursor: pointer;">
                    <i class="bi bi-moon-fill fs-5 hover-primary dark-mode-toggle text-dark"></i>
                </div>
                
                <div class="position-relative me-3">
                    <i class="bi bi-bell-fill fs-5 hover-primary notification-bell text-dark" style="cursor: pointer;"></i>
                    
                    {{-- Badge Notif (Awalnya Hidden) --}}
                    <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge">0</span>
                    
                    <div class="notification-dropdown" style="width: 320px;">
                        <div class="notification-header d-flex justify-content-between align-items-center">
                            <span>Notifikasi</span>
                            <span class="badge bg-primary rounded-pill" id="notificationCountBadge">0 Baru</span>
                        </div>

                        <div style="max-height: 300px; overflow-y: auto;" id="notificationList">
                            @if(isset($globalNotifications) && count($globalNotifications) > 0)
                                @foreach($globalNotifications as $notif)
                                    {{-- PENTING: Tambah data-notification-id untuk JS --}}
                                    <a href="{{ $notif['url'] }}" class="text-decoration-none text-dark notification-link" data-notification-id="{{ $notif['id'] ?? '' }}">
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
                                                    <small class="text-muted d-block" style="line-height: 1.2;">{{ $notif['message'] }}</small>
                                                    <small class="text-secondary" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($notif['time'])->diffForHumans() }}</small>
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
                
                @auth
                    @php
                        $user = Auth::user();
                        $employee = $user->employee;
                        $foto = $employee->foto_profil ?? null;
                        $name = $employee->nama ?? $user->username;
                        $initial = strtoupper(substr($name, 0, 1));
                    @endphp
                    
                    <a href="/profil" class="d-flex align-items-center text-decoration-none text-dark">
                        @if($foto)
                            <img src="{{ asset($foto) }}" class="rounded-circle shadow-sm" alt="Profil" style="width: 40px; height: 40px; object-fit: cover;">
                        @else
                            <div class="avatar-initial shadow-sm">{{ $initial }}</div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- SIDEBAR & DARK MODE (Standard) ---
            const toggleBtn = document.getElementById('sidebarToggle');
            const body = document.body;
            const sidebar = document.getElementById('sidebar');
            function isMobile() { return window.innerWidth <= 992; }
            if(toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    isMobile() ? (body.classList.toggle('sidebar-open'), body.classList.remove('sidebar-collapsed')) : (body.classList.toggle('sidebar-collapsed'), body.classList.remove('sidebar-open'));
                });
            }
            document.addEventListener('click', function(e) {
                if (isMobile() && body.classList.contains('sidebar-open') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    body.classList.remove('sidebar-open');
                }
            });
            const darkToggle = document.querySelector('.dark-mode-toggle');
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                body.classList.add('dark-mode');
                if(darkToggle) { darkToggle.classList.remove('bi-moon-fill'); darkToggle.classList.add('bi-sun-fill'); }
            }
            if(darkToggle) {
                darkToggle.addEventListener('click', function() {
                    body.classList.toggle('dark-mode');
                    const isNowDark = body.classList.contains('dark-mode');
                    this.classList.toggle('bi-moon-fill'); this.classList.toggle('bi-sun-fill');
                    localStorage.setItem('darkMode', isNowDark);
                });
            }

            // --- NOTIFICATION SYSTEM (FIXED) ---
            const notifBell = document.querySelector('.notification-bell');
            const notifDrop = document.querySelector('.notification-dropdown');
            const notifBadge = document.getElementById('notificationBadge');
            const notifCountBadge = document.getElementById('notificationCountBadge');
            const notifLinks = document.querySelectorAll('.notification-link');
            
            // 1. Baca Cookie 'seen_notifications'
            function getSeenNotifications() {
                const match = document.cookie.match(new RegExp('(^| )seen_notifications=([^;]+)'));
                if (match) {
                    try {
                        return JSON.parse(decodeURIComponent(match[2]));
                    } catch (e) { return []; }
                }
                return [];
            }

            // 2. Simpan Cookie
            function saveSeenNotifications(ids) {
                const d = new Date();
                d.setTime(d.getTime() + (30*24*60*60*1000));
                const expires = "expires="+ d.toUTCString();
                document.cookie = "seen_notifications=" + encodeURIComponent(JSON.stringify(ids)) + ";" + expires + ";path=/";
            }

            // 3. Hitung Unread saat Load
            const seenIds = getSeenNotifications();
            const currentIds = Array.from(notifLinks).map(link => link.getAttribute('data-notification-id'));
            
            // Filter: ID yang ada di halaman tapi belum ada di cookie
            const unreadIds = currentIds.filter(id => !seenIds.includes(id));
            const unreadCount = unreadIds.length;

            // Tampilkan Badge jika ada unread
            if (unreadCount > 0) {
                if(notifBadge) {
                    notifBadge.style.display = 'inline-block';
                    notifBadge.textContent = unreadCount;
                }
                if(notifCountBadge) {
                    notifCountBadge.style.display = 'inline-block';
                    notifCountBadge.textContent = unreadCount + ' Baru';
                }
            } else {
                if(notifBadge) notifBadge.style.display = 'none';
                if(notifCountBadge) notifCountBadge.style.display = 'none';
            }

            // 4. Event Click Lonceng -> Tandai Semua "Seen"
            if(notifBell) {
                notifBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = notifDrop.style.display === 'block';
                    notifDrop.style.display = isVisible ? 'none' : 'block';
                    
                    if (!isVisible) { // Artinya sekarang jadi visible (dibuka)
                        // Tambahkan semua ID yang ada sekarang ke cookie seen
                        const updatedSeen = [...new Set([...seenIds, ...currentIds])];
                        saveSeenNotifications(updatedSeen);

                        // Sembunyikan Badge
                        if(notifBadge) notifBadge.style.display = 'none';
                        if(notifCountBadge) notifCountBadge.style.display = 'none';
                    }
                });
            }

            // Tutup Dropdown saat klik luar
            document.addEventListener('click', function() {
                if(notifDrop) notifDrop.style.display = 'none';
            });
            if(notifDrop) {
                notifDrop.addEventListener('click', function(e) { e.stopPropagation(); });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>