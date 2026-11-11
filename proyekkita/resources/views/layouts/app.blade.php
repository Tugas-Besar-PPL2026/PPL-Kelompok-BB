@php
    $authUser = auth()->user();

    $navName = match (true) {
        $authUser->isUmkm() => $authUser->umkmProfile?->business_name ?? $authUser->name,
        $authUser->isKandidat() => $authUser->candidateProfile?->full_name ?? $authUser->name,
        default => $authUser->name,
    };

    $navRole = match (true) {
        $authUser->isAdmin() => 'Administrator',
        $authUser->isKandidat() && $authUser->candidateProfile?->university
            => 'Kandidat · ' . $authUser->candidateProfile->university,
        default => $authUser->role->label(),
    };

    // Mengambil maksimal dua inisial dari nama pengguna.
    $navInitials = collect(preg_split('/\s+/', trim($navName)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');

    // Menggunakan relasi notifications()->unread() yang sesuai dengan model kelompokmu
    $navUnread = $authUser->notifications()->unread()->count();
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Beranda') · ProyekKita</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f8f7ff;
            color: #202230;
            font-size: 13px;
        }

        /* NAVBAR */
        .topbar {
            height: 47px;
            width: 100%;
            padding: 0 18px;
            background: #ffffff;
            border-bottom: 1px solid #e7e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #155eef;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .topbar-notif {
            position: relative;
            display: flex;
            align-items: center;
            color: #42465a;
            text-decoration: none;
            padding: 6px;
        }

        .topbar-notif .cnt {
            position: absolute;
            top: 0;
            right: -2px;
            background: #dc2626;
            color: #ffffff;
            font-size: 9px;
            min-width: 15px;
            height: 15px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 9px;
            padding-left: 12px;
            border-left: 1px solid #e7e8f0;
        }

        .topbar-user .av {
            width: 27px;
            height: 27px;
            border-radius: 7px;
            background: #e8edff;
            color: #155eef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }

        .topbar-user .n {
            font-weight: 600;
            font-size: 11px;
            color: #25283a;
        }

        .topbar-user .sub {
            font-size: 10px;
            color: #85899d;
            margin-top: 2px;
        }

        /* LAYOUT UTAMA (.wrap) */
        .wrap {
            display: flex;
            width: 100%;
            min-height: calc(100vh - 47px);
            align-items: stretch;
            margin: 0;
            padding: 0;
        }

        .main {
            flex: 1;
            min-width: 0;
            padding: 24px 26px;
            background: #f8f7ff;
            overflow-x: auto;
        }

        /* SIDEBAR */
        .side {
            width: 184px;
            min-width: 184px;
            flex: 0 0 184px;
            padding: 13px 10px;
            background: #ffffff;
            border-right: 1px solid #e7e8f0;
            display: flex;
            flex-direction: column;
        }

        .sidehead {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px;
            margin-bottom: 12px;
            border: 1px solid #e4e7f0;
            border-radius: 9px;
            min-height: 49px;
        }

        .sidehead .av {
            width: 27px;
            height: 27px;
            min-width: 27px;
            border-radius: 7px;
            background: #e8edff;
            color: #155eef;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 10px;
            font-weight: 700;
        }

        .sidehead .n {
            font-size: 10px;
            font-weight: 700;
            color: #25283a;
            line-height: 1.4;
        }

        .sidehead .sub {
            display: block;
            color: #85899d;
            font-size: 9px;
        }

        .sidehead .bd {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 9px;
        }

        .mi {
            display: flex;
            width: 100%;
            min-height: 33px;
            padding: 8px 10px;
            align-items: center;
            gap: 9px;
            margin-bottom: 3px;
            border-radius: 7px;
            color: #52576b;
            text-decoration: none;
            font-size: 11px;
            font-weight: 500;
            background: transparent;
            border: none;
            cursor: pointer;
            font-family: inherit;
            text-align: left;
            transition: background 0.2s, color 0.2s;
        }

        .mi:hover {
            background: #f1f4ff;
            color: #155eef;
        }

        .mi.on {
            color: #ffffff;
            background: #2861eb;
            font-weight: 600;
        }

        .mi .ic {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .mi .cnt {
            margin-left: auto;
            min-width: 17px;
            height: 17px;
            border-radius: 20px;
            background: #e8edff;
            color: #155eef;
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mi.on .cnt {
            background: #ffffff;
            color: #155eef;
        }

        .sidefoot {
            margin-top: auto;
            padding-top: 11px;
            border-top: 1px solid #e7e8f0;
        }

        .mi.danger {
            color: #dc2626;
        }

        .mi.danger:hover {
            background: #fef2f2;
        }

        /* ALERT */
        .al {
            display: flex;
            align-items: flex-start;
            gap: 9px;
        }
    </style>

    @stack('styles')
</head>

<body>

    {{-- NAVBAR --}}
    <header class="topbar">
        <a href="{{ url('/') }}" class="brand">
            <i data-lucide="network" width="19" height="19"></i>
            <span>ProyekKita</span>
        </a>

        <div class="topbar-right">
            <a href="{{ Route::has('notifikasi.index') ? route('notifikasi.index') : '#' }}"
               class="topbar-notif">
                <i data-lucide="bell" width="17" height="17"></i>

                @if ($navUnread > 0)
                    <span class="cnt">{{ $navUnread }}</span>
                @endif
            </a>

            <div class="topbar-user">
                <div class="av">{{ $navInitials }}</div>

                <div>
                    <div class="n">{{ $navName }}</div>
                    <div class="sub">{{ $navRole }}</div>
                </div>
            </div>
        </div>
    </header>

    {{-- LAYOUT UTAMA --}}
    <div class="wrap">

        @include('layouts.partials.sidebar')

        <main class="main">

            @if (session('success'))
                <div class="al ok mb16"
                     style="margin-bottom:16px; padding:12px; border-radius:8px; background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; font-size:13px;">
                    <i data-lucide="circle-check" width="19" height="19"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="al bad mb16"
                     style="margin-bottom:16px; padding:12px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; font-size:13px;">
                    <i data-lucide="circle-alert" width="19" height="19"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @yield('content')

        </main>
    </div>

    @stack('modals')

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>

    @stack('scripts')

</body>
</html>