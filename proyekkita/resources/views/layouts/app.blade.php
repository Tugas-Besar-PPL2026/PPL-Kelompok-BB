{{--
    Layout utama untuk semua halaman setelah login (admin, UMKM, kandidat).
    Navbar dan sidebar sudah ada di sini, jadi halaman lain cukup:

        @extends('layouts.app')
        @section('title', 'Judul Halaman')
        @section('content') ...isi halaman... @endsection

    Pesan session('success') dan session('error') juga ditampilkan otomatis di sini.
--}}
@php
    $authUser = auth()->user();

    $navName = match (true) {
        $authUser->isUmkm() => $authUser->umkmProfile?->business_name ?? $authUser->name,
        $authUser->isKandidat() => $authUser->candidateProfile?->full_name ?? $authUser->name,
        default => $authUser->name,
    };

    $navRole = match (true) {
        $authUser->isAdmin() => 'Administrator',
        $authUser->isKandidat() && $authUser->candidateProfile?->university => 'Kandidat · ' . $authUser->candidateProfile->university,
        default => $authUser->role->label(),
    };

    $navInitials = collect(preg_split('/\s+/', trim($navName)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->join('');

    $navUnread = $authUser->notifications()->unread()->count();
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') · ProyekKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
    @include('layouts.partials.navbar')

    <div class="wrap">
        @include('layouts.partials.sidebar')

        <main class="main">
            @if (session('success'))
                <div class="al ok mb16"><i data-lucide="circle-check" class="ic" width="19" height="19"></i><div>{{ session('success') }}</div></div>
            @endif
            @if (session('error'))
                <div class="al bad mb16"><i data-lucide="circle-alert" class="ic" width="19" height="19"></i><div>{{ session('error') }}</div></div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('modals')

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <script>lucide.createIcons();</script>
    @stack('scripts')
</body>
</html>
