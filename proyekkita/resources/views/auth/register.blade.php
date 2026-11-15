{{-- [FR-01] Registrasi akun. Variabel $role: 'umkm' atau 'kandidat' (dari route daftar/{role}). --}}
@extends('layouts.guest')

@php
    $isUmkm = $role === 'umkm';
    $roleLabel = $isUmkm ? 'UMKM' : 'Kandidat';
@endphp

@section('title', 'Daftar sebagai '.$roleLabel)

@section('content')
<div class="pk-split">
    @include('auth._aside', $isUmkm ? [
        'title' => 'Daftar sebagai UMKM',
        'lead' => 'Publikasikan kebutuhan proyek dan temukan mahasiswa atau lulusan baru yang sesuai.',
        'points' => [
            'Lengkapi Profil Usaha setelah mendaftar',
            'Admin memverifikasi akun UMKM sebelum Anda dapat mengajukan proyek',
            'Selama belum terverifikasi, Anda tetap dapat masuk, mengelola profil, dan menyimpan draf proyek',
        ],
    ] : [
        'title' => 'Daftar sebagai Kandidat',
        'lead' => 'Cari proyek jangka pendek dari UMKM dan bangun rekam pengalaman kerja Anda.',
        'points' => [
            'Lengkapi profil, keterampilan, pengalaman, dan portofolio',
            'Lamar proyek yang sesuai dengan kemampuan Anda',
            'Riwayat proyek dan penilaian UMKM tersimpan di profil Anda',
        ],
    ])

    <div class="pk-main">
        <div class="pk-card pk-card--wide">
            <h1>Buat akun</h1>
            <p class="pk-sub">Pilih peran Anda terlebih dahulu.</p>

            {{-- Pilihan peran: berpindah antar halaman registrasi UMKM / Kandidat. --}}
            <div class="pk-roles">
                <a href="{{ route('register', ['role' => 'umkm']) }}" class="pk-role {{ $isUmkm ? 'is-on' : '' }}" dusk="peran-umkm" @if ($isUmkm) aria-current="true" @endif>
                    <span class="pk-role-ico"><i data-lucide="store" class="ic" width="18" height="18"></i></span>
                    <span><b>UMKM</b><span class="d">Saya membutuhkan bantuan proyek</span></span>
                </a>
                <a href="{{ route('register', ['role' => 'kandidat']) }}" class="pk-role {{ ! $isUmkm ? 'is-on' : '' }}" dusk="peran-kandidat" @if (! $isUmkm) aria-current="true" @endif>
                    <span class="pk-role-ico"><i data-lucide="graduation-cap" class="ic" width="18" height="18"></i></span>
                    <span><b>Kandidat</b><span class="d">Mahasiswa / lulusan baru</span></span>
                </a>
            </div>

            <form method="POST" action="{{ route('register.store', ['role' => $role]) }}" novalidate data-auth-form>
                @csrf

                <div class="pk-field">
                    <label for="name">{{ $isUmkm ? 'Nama penanggung jawab' : 'Nama lengkap' }} <span class="req">*</span></label>
                    <div class="pk-input {{ $errors->has('name') ? 'is-invalid' : '' }}">
                        <i data-lucide="user" class="ic" width="17" height="17"></i>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="100" required>
                    </div>
                    @error('name')<p class="pk-err" dusk="galat-name">{{ $message }}</p>@enderror
                </div>

                <div class="pk-field">
                    <label for="email">Email <span class="req">*</span></label>
                    <div class="pk-input {{ $errors->has('email') ? 'is-invalid' : '' }}">
                        <i data-lucide="mail" class="ic" width="17" height="17"></i>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                    </div>
                    @error('email')
                        <p class="pk-err" dusk="galat-email">{{ $message }}</p>
                    @else
                        <p class="pk-help">Email digunakan untuk masuk dan harus belum terdaftar.</p>
                    @enderror
                </div>

                <div class="pk-two" style="margin-top:20px">
                    <div class="pk-field" style="margin-top:0">
                        <label for="password">Kata sandi <span class="req">*</span></label>
                        <div class="pk-input {{ $errors->has('password') ? 'is-invalid' : '' }}">
                            <i data-lucide="lock" class="ic" width="17" height="17"></i>
                            <input id="password" name="password" type="password" autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="pk-field" style="margin-top:0">
                        <label for="password_confirmation">Konfirmasi kata sandi <span class="req">*</span></label>
                        <div class="pk-input">
                            <i data-lucide="lock" class="ic" width="17" height="17"></i>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                        </div>
                    </div>
                </div>
                @error('password')
                    <p class="pk-err" dusk="galat-password">{{ $message }}</p>
                @else
                    <p class="pk-help">Minimal 8 karakter.</p>
                @enderror

                <label class="pk-check">
                    <input id="terms" name="terms" type="checkbox" value="1" @checked(old('terms')) required>
                    <span>Saya menyetujui syarat dan ketentuan penggunaan ProyekKita.</span>
                </label>
                @error('terms')<p class="pk-err" dusk="galat-terms">{{ $message }}</p>@enderror

                <div class="pk-info">
                    <i data-lucide="info" class="ic" width="18" height="18"></i>
                    <div>
                        @if ($isUmkm)
                            Setelah mendaftar, Anda diarahkan ke Profil Usaha untuk melengkapi data usaha dan NIB/tautan usaha sebagai bahan verifikasi admin.
                        @else
                            Setelah mendaftar, Anda diarahkan ke Profil &amp; Portofolio untuk melengkapi data sebelum melamar proyek.
                        @endif
                    </div>
                </div>

                <button type="submit" class="pk-btn pk-btn--p pk-btn--block" dusk="daftar">
                    <i data-lucide="user-plus" class="ic" width="16" height="16"></i>Daftar sebagai {{ $roleLabel }}
                </button>
            </form>

            <div class="pk-card-foot">
                Sudah punya akun? <a href="{{ route('login') }}" dusk="ke-masuk">Masuk</a>
            </div>
        </div>
    </div>
</div>
@endsection
