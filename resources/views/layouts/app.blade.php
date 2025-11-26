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

        /* === SIDEBAR STYLE (DIPERBAIKI) === */
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
            /* Mencegah scroll horizontal jika konten sidebar lebar */
            overflow-x: hidden;
        }

        /* === MAIN WRAPPER (FOOTER FIX) === */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            /* Kunci agar footer terdorong ke bawah */
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* Susunan vertikal */
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

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: var(--primary-color);
            border-radius: 0.25rem;
        }
        .sidebar-footer {
            margin-top: auto;
            padding-bottom: 1rem;
        }

        /* === TOPBAR & FOOTER === */
        .topbar {
            background-color: #fff;
            height: var(--topbar-height);
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: sticky; top: 0; z-index: 999;
        }

        /* PERBAIKAN FOOTER: Push Bottom (Bukan Sticky/Fixed) */
        .main-footer {
            background-color: #e9ecef;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            /* margin-top: auto akan mendorong footer ke paling bawah sisa ruang */
            margin-top: auto;
            transition: all 0.3s ease;
            padding: 1rem; /* Tambahkan padding agar rapi */
        }

        /* === DARK MODE STYLES === */
        .hover-primary:hover { color: var(--primary-color) !important; transition: color 0.3s ease; }

        .notification-badge {
            font-size: 0.6rem;
            padding: 0.25em 0.4em;
            display: none; /* Default hidden */
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

    <nav class="sidebar d-flex flex-column pt-3">
    <div>
        <a href="#" class="d-flex align-items-center mb-4 text-white text-decoration-none px-3">
            <i class="bi bi-building-check fs-2 me-2"></i>
            <span class="fs-4 fw-bold">Portal Absensi</span>
        </a>

        @php
            $role = strtolower(Auth::user()->role ?? '');
        @endphp

        <ul class="nav nav-pills flex-column">

            {{-- 1. DASHBOARD KARYAWAN (Hanya muncul jika user asli Karyawan) --}}
            @if($role === 'karyawan')
                <li class="nav-item mb-1">
                    <a href="{{ route('karyawan.dashboard') }}" class="nav-link {{ request()->routeIs('karyawan.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-fill me-2"></i> Dashboard
                    </a>
                </li>
            @endif

            {{-- 2. FITUR UTAMA (Muncul untuk SEMUA: Karyawan, Admin, Manajemen) --}}
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('karyawan.unggah') ? 'active' : '' }}" href="{{ route('karyawan.unggah') }}">
                    <i class="bi bi-camera-fill me-2"></i> Unggah Absensi
                </a>
            </li>

            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('karyawan.riwayat') ? 'active' : '' }}" href="{{ route('karyawan.riwayat') }}">
                    <i class="bi bi-clock-history me-2"></i> Riwayat Absensi
                </a>
            </li>

            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('karyawan.izin') ? 'active' : '' }}" href="{{ route('karyawan.izin') }}">
                    <i class="bi bi-envelope-paper-fill me-2"></i> Pengajuan Izin
                </a>
            </li>

            {{-- 3. TOMBOL KEMBALI (Khusus Admin & Manajemen) --}}
            @if($role === 'admin' || $role === 'manajemen')
                <hr class="text-white my-3">

                @if($role === 'admin')
                    <li class="nav-item mb-1">
                        <small class="text-white-50 ms-3 text-uppercase" style="font-size: 0.75rem;">Akses Admin</small>
                        <a href="{{ route('admin.dashboard') }}" class="nav-link text-warning fw-bold mt-1" style="background: rgba(255, 193, 7, 0.1);">
                            <i class="bi bi-arrow-left-circle-fill me-2"></i> Kembali ke Admin
                        </a>
                    </li>
                @endif

                @if($role === 'manajemen')
                    <li class="nav-item mb-1">
                        <small class="text-white-50 ms-3 text-uppercase" style="font-size: 0.75rem;">Akses Manajemen</small>
                        <a href="{{ route('manajemen.dashboard') }}" class="nav-link text-info fw-bold mt-1" style="background: rgba(13, 202, 240, 0.1);">
                            <i class="bi bi-arrow-left-circle-fill me-2"></i> Kembali ke Manajemen
                        </a>
                    </li>
                @endif
            @endif

        </ul>
    </div>

    <div class="sidebar-footer mt-auto">
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a href="{{ route('karyawan.profil') }}" class="nav-link {{ request()->routeIs('karyawan.profil') ? 'active' : '' }}">
                    <i class="bi bi-person-circle me-2"></i> Profil Saya
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('logout') }}" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-left me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

    {{-- Main Wrapper (Perbaikan Flex Column) --}}
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

                    {{-- Badge Notif --}}
                    <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge">0</span>

                    <div class="notification-dropdown" style="width: 320px;">
                        <div class="notification-header d-flex justify-content-between align-items-center">
                            <span>Notifikasi</span>
                            <span class="badge bg-primary rounded-pill" id="notificationCountBadge">0 Baru</span>
                        </div>

                        <div style="max-height: 300px; overflow-y: auto;" id="notificationList">
                            @if(isset($globalNotifications) && count($globalNotifications) > 0)
                                @foreach($globalNotifications as $notif)
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

        {{-- Main Content: Flex Grow untuk mengisi ruang kosong --}}
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
            // --- SIDEBAR & DARK MODE (Standard) ---
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
                if (isMobile() && body.classList.contains('sidebar-open') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    body.classList.remove('sidebar-open');
                }
            });

            const darkToggle = document.querySelector('.dark-mode-toggle');
            const isDarkMode = localStorage.getItem('darkMode') === 'true';

            if (isDarkMode) {
                body.classList.add('dark-mode');
                if(darkToggle) {
                    darkToggle.classList.remove('bi-moon-fill');
                    darkToggle.classList.add('bi-sun-fill');
                }
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

            // --- NOTIFICATION SYSTEM (FIXED) ---

            const serverNotifCount = parseInt("{{ $notifCount ?? 0 }}");

            const notifBell = document.querySelector('.notification-bell');
            const notifDrop = document.querySelector('.notification-dropdown');
            const notifBadge = document.getElementById('notificationBadge');
            const notifCountBadge = document.getElementById('notificationCountBadge');
            const notifLinks = document.querySelectorAll('.notification-link');

            function getSeenNotifications() {
                try {
                    const cookieValue = document.cookie
                        .split('; ')
                        .find(row => row.startsWith('seen_notifications='));

                    return cookieValue ? JSON.parse(decodeURIComponent(cookieValue.split('=')[1])) : [];
                } catch (error) {
                    console.error('Error reading seen notifications:', error);
                    return [];
                }
            }

            function markNotificationsAsSeen(notificationIds) {
                try {
                    const seenNotifications = getSeenNotifications();
                    const updatedSeenNotifications = [...new Set([...seenNotifications, ...notificationIds])];

                    const expires = new Date();
                    expires.setDate(expires.getDate() + 30);
                    document.cookie = 'seen_notifications=' + encodeURIComponent(JSON.stringify(updatedSeenNotifications)) + '; expires=' + expires.toUTCString() + '; path=/';

                    const remainingCount = Math.max(0, serverNotifCount - updatedSeenNotifications.length);

                    if (notifBadge) {
                        if (remainingCount <= 0) {
                            notifBadge.style.display = 'none';
                        } else {
                            notifBadge.textContent = remainingCount;
                            notifBadge.style.display = 'inline-block';
                        }
                    }

                    if (notifCountBadge) {
                        if (remainingCount <= 0) {
                            notifCountBadge.style.display = 'none';
                        } else {
                            notifCountBadge.textContent = remainingCount + ' Baru';
                            notifCountBadge.style.display = 'inline-block';
                        }
                    }
                    return true;
                } catch (error) {
                    console.error('Error marking notifications as seen:', error);
                    return false;
                }
            }

            if(notifBell) {
                notifBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = notifDrop.style.display === 'block';
                    notifDrop.style.display = isVisible ? 'none' : 'block';

                    if (!isVisible) {
                        const notificationIds = [];
                        notifLinks.forEach(function(link) {
                            const notifId = link.getAttribute('data-notification-id');
                            if (notifId) notificationIds.push(notifId);
                        });

                        if (notificationIds.length > 0) {
                            markNotificationsAsSeen(notificationIds);
                        }
                    }
                });
            }

            notifLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    const notifId = this.getAttribute('data-notification-id');
                    if (notifId) {
                        markNotificationsAsSeen([notifId]);
                    }
                });
            });

            document.addEventListener('click', function() {
                if(notifDrop) {
                    notifDrop.style.display = 'none';
                }
            });

            if(notifDrop) {
                notifDrop.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            function initializeNotificationBadge() {
                const seenNotifications = getSeenNotifications();
                const remainingCount = Math.max(0, serverNotifCount - seenNotifications.length);

                if (notifBadge) {
                    if (remainingCount <= 0) {
                        notifBadge.style.display = 'none';
                    } else {
                        notifBadge.textContent = remainingCount;
                        notifBadge.style.display = 'inline-block';
                    }
                }

                if (notifCountBadge) {
                    if (remainingCount <= 0) {
                        notifCountBadge.style.display = 'none';
                    } else {
                        notifCountBadge.textContent = remainingCount + ' Baru';
                        notifCountBadge.style.display = 'inline-block';
                    }
                }
            }

            initializeNotificationBadge();
        });
    </script>

    @stack('scripts')
</body>
</html>
