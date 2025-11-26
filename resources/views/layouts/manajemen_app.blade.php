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
        .notification-badge { font-size: 0.6rem; padding: 0.25em 0.4em; display: none; }

        /* === STYLE NOTIFIKASI === */
        .notif-item {
            display: flex; gap: 12px; padding: 12px 16px; border-radius: 6px; transition: background 0.2s;
        }
        .notif-item:hover { background: #f8f9fa; }
        .notif-icon {
            width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .notif-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; color: #333; }
        .notif-msg { font-size: 0.85rem; color: #666; line-height: 1.3; }
        .notif-time { font-size: 0.75rem; color: #999; margin-top: 4px; display: flex; align-items: center; gap: 4px; }
        .notif-empty { padding: 20px; font-size: .9rem; color:#6c757d; text-align: center; }

        /* === DARK MODE === */
        .dark-mode { background-color: #1a1a1a; color: #e0e0e0; }
        .dark-mode .topbar { background-color: #2d2d2d; border-bottom-color: #444; color: #fff; }
        .dark-mode .main-footer { background-color: #2d2d2d; border-top-color: #444; color: #aaa; }
        .dark-mode .card { background-color: #2d2d2d; border-color: #444; color: #fff; }
        .dark-mode .card-header, .dark-mode .card-footer { background-color: #333; border-color: #444; color: #fff; }
        .dark-mode .dropdown-menu { background-color: #2d2d2d; border-color: #444; }
        .dark-mode .dropdown-item { color: #e0e0e0; }
        .dark-mode .dropdown-item:hover { background-color: #3a3a3a; }
        .dark-mode .dropdown-header { color: #fff; }
        .dark-mode .dropdown-divider { border-top-color: #444; }
        .dark-mode .notif-title { color: #fff; }
        .dark-mode .notif-msg { color: #ccc; }
        .dark-mode .notif-item:hover { background: #3a3a3a; }
        .dark-mode .table { color: #e0e0e0; border-color: #444; }
        .dark-mode .table-light th { background-color: #333; color: #fff; border-color: #444; }
        .dark-mode .table-hover tbody tr:hover { background-color: #3a3a3a; color: #fff; }
        .dark-mode .form-control, .dark-mode .form-select { background-color: #2b2b2b; border-color: #444; color: #fff; }
        .dark-mode .form-control:focus { background-color: #333; color: #fff; border-color: #0d6efd; }
        .dark-mode .bg-light { background-color: #2b2b2b !important; }
        .dark-mode .text-muted { color: #adb5bd !important; }
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
                    <a class="nav-link {{ request()->routeIs('karyawan.unggah') ? 'active' : '' }}" href="{{ route('karyawan.unggah') }}">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i> Portal Absensi
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="/manajemen/dashboard" class="nav-link {{ Request::is('manajemen/dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-fill me-2"></i> Dashboard & Analisis
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('manajemen.validasi.show') }}" class="nav-link {{ request()->routeIs('manajemen.validasi.*') ? 'active' : '' }}">
                        <i class="bi bi-check-circle-fill me-2"></i> Validasi & Approval
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/manajemen/laporan" class="nav-link {{ Request::is('manajemen/laporan') ? 'active' : '' }}">
                        <i class="bi bi-table me-2"></i> Data Laporan
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

                {{-- ✅ DROP DOWN NOTIFIKASI MANAJEMEN (PERBAIKAN UTAMA) --}}
                <div class="dropdown me-3">
                    <div class="position-relative" id="mgmtNotifToggle" data-bs-toggle="dropdown" style="cursor:pointer;">
                        <i class="bi bi-bell-fill fs-5 hover-primary"></i>
                        
                        {{-- Badge Merah --}}
                        <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="mgmtNotifBadge">0</span>
                    </div>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 360px;">
                        <li class="dropdown-header fw-bold d-flex justify-content-between align-items-center py-2">
                            <span class="fs-6">Notifikasi</span>
                            <span class="badge bg-primary rounded-pill" id="mgmtNotifCountLabel">0 Baru</span>
                        </li>
                        <li><hr class="dropdown-divider my-0"></li>

                        <div style="max-height: 350px; overflow-y: auto;">
                            @forelse ($manajemenNotifList ?? [] as $n)
                                <li>
                                    {{-- Link Notifikasi --}}
                                    <a href="{{ $n['url'] }}" class="dropdown-item p-0 notif-link-item" data-id="{{ $n['id'] }}" style="white-space: normal;">
                                        <div class="notif-item">
                                            {{-- Ikon berdasarkan tipe --}}
                                            @if($n['type'] == 'absensi')
                                                <div class="notif-icon bg-primary text-white">
                                                    <i class="bi bi-camera-fill"></i>
                                                </div>
                                            @else
                                                <div class="notif-icon bg-warning text-dark">
                                                    <i class="bi bi-envelope-paper-fill"></i>
                                                </div>
                                            @endif

                                            <div class="flex-grow-1">
                                                <div class="notif-title">{{ $n['title'] }}</div>
                                                <div class="notif-msg">{{ $n['message'] }}</div>
                                                <div class="notif-time">
                                                    <i class="bi bi-clock"></i>
                                                    {{ \Carbon\Carbon::parse($n['time'])->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-0"></li>
                            @empty
                                <li class="notif-empty text-center py-4">
                                    <i class="bi bi-bell-slash fs-3 mb-2 d-block"></i>
                                    Semua tugas sudah selesai.
                                </li>
                            @endforelse
                        </div>
                    </ul>
                </div>

                @php
                    $user = Auth::user();
                    $employee = $user->employee;
                    $namaLengkap = $employee->nama ?? $user->username;
                    $initial = strtoupper(substr($namaLengkap, 0, 1));
                    $roleDisplay = ucfirst($user->role);
                    $fotoProfil = $employee->foto_profil ?? null;
                @endphp

                <a href="{{ route('manajemen.profil') }}" class="d-flex align-items-center text-decoration-none text-dark profile-link">
                    <div class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center overflow-hidden border shadow-sm"
                         style="width:40px;height:40px;">
                        @if($fotoProfil)
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
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="main-footer d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <strong class="me-3">Klinik Grand Warden</strong>
                <span class="text-muted d-none d-md-inline">Jl. Medan Merdeka Timur No.11-13 Clash Universe</span>
            </div>

            <div class="d-flex">
                <a href="https://www.facebook.com" target="_blank" class="text-decoration-none me-3">
                    <i class="bi bi-facebook fs-6 text-muted hover-primary"></i>
                </a>
                <a href="https://twitter.com" target="_blank" class="text-decoration-none me-3">
                    <i class="bi bi-twitter-x fs-6 text-muted hover-primary"></i>
                </a>
                <a href="https://www.instagram.com" target="_blank" class="text-decoration-none">
                    <i class="bi bi-instagram fs-6 text-muted hover-primary"></i>
                </a>
            </div>
        </footer>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. DARK MODE ---
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

        // --- 2. NOTIFIKASI LOGIC (BADGE COUNTER) ---
        const notifLinks = document.querySelectorAll('.notif-link-item');
        const badge = document.getElementById('mgmtNotifBadge');
        const badgeLabel = document.getElementById('mgmtNotifCountLabel');
        const toggleBell = document.getElementById('mgmtNotifToggle');

        // Helper: Baca Cookie
        function getSeenIds() {
            const match = document.cookie.match(new RegExp('(^| )mgmt_seen_notifs=([^;]+)'));
            if (match) {
                try {
                    return JSON.parse(decodeURIComponent(match[2]));
                } catch (e) { return []; }
            }
            return [];
        }

        // Helper: Simpan Cookie
        function saveSeenIds(ids) {
            const d = new Date();
            d.setTime(d.getTime() + (30*24*60*60*1000));
            const expires = "expires="+ d.toUTCString();
            document.cookie = "mgmt_seen_notifs=" + encodeURIComponent(JSON.stringify(ids)) + ";" + expires + ";path=/";
        }

        // Hitung Unread saat load page
        const seenIds = getSeenIds();
        const currentIds = Array.from(notifLinks).map(el => el.getAttribute('data-id'));
        const unreadIds = currentIds.filter(id => !seenIds.includes(id));
        const unreadCount = unreadIds.length;

        // Update Badge Tampilan
        if (unreadCount > 0) {
            if(badge) {
                badge.style.display = 'inline-block';
                badge.innerText = unreadCount;
            }
            if(badgeLabel) {
                badgeLabel.innerText = unreadCount + ' Baru';
                badgeLabel.style.display = 'inline-block';
            }
        } else {
            if(badge) badge.style.display = 'none';
            if(badgeLabel) badgeLabel.style.display = 'none';
        }

        // Saat Lonceng diklik -> Tandai semua di list ini sebagai 'SEEN'
        if (toggleBell) {
            toggleBell.addEventListener('show.bs.dropdown', function () {
                const updatedSeenIds = [...new Set([...seenIds, ...currentIds])];
                saveSeenIds(updatedSeenIds);
                if(badge) badge.style.display = 'none';
                if(badgeLabel) badgeLabel.style.display = 'none';
            });
        }
    });
</script>

@stack('scripts')
</body>
</html>