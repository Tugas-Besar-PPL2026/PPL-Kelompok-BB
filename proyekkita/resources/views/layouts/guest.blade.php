{{--
    Layout halaman publik (tamu): Landing, Masuk, dan Registrasi [FR-01].
    Berbeda dengan layouts/app.blade.php yang khusus halaman setelah login (butuh pengguna terautentikasi).

        @extends('layouts.guest')
        @section('title', 'Judul')
        @section('nav_active', 'cara-kerja')      (opsional: tautan navigasi yang ditandai aktif)
        @section('actions') ...tombol di kanan header... @endsection   (opsional)
        @section('content') ... @endsection
--}}
@php $navActive = trim($__env->yieldContent('nav_active')); @endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ProyekKita') · ProyekKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <header class="pk-top">
        <div class="pk-top-in">
            <a href="{{ route('landing') }}" class="pk-logo">
                <i data-lucide="network" class="ic" width="26" height="26"></i>ProyekKita
            </a>

            <nav class="pk-nav" aria-label="Navigasi utama">
                <a href="{{ route('landing') }}#cara-kerja" class="{{ $navActive === 'cara-kerja' ? 'on' : '' }}">Cara Kerja</a>
                <a href="{{ route('landing') }}#untuk-umkm">Untuk UMKM</a>
                <a href="{{ route('landing') }}#untuk-kandidat">Untuk Mahasiswa &amp; Lulusan Baru</a>
            </nav>

            @hasSection('actions')
                <div class="pk-actions">@yield('actions')</div>
            @endif
        </div>
    </header>

    @yield('content')

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <script>lucide.createIcons();</script>
    <script src="{{ asset('js/auth.js') }}" defer></script>
</body>
</html>
