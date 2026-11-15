{{-- [FR-01] Halaman Masuk: satu pintu untuk Admin, UMKM, dan Kandidat. --}}
@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<div class="pk-split">
    @include('auth._aside', [
        'title' => 'Selamat datang kembali',
        'lead' => 'Masuk untuk mengelola proyek, lamaran, kesepakatan, dan pembayaran Anda.',
        'points' => [
            'Satu halaman masuk untuk Admin, UMKM, dan Kandidat',
            'Anda diarahkan ke halaman sesuai peran akun',
            'Kata sandi disimpan dalam bentuk terenkripsi (hash)',
        ],
    ])

    <div class="pk-main">
        <div class="pk-card">
            <h1>Masuk</h1>
            <p class="pk-sub">Gunakan email dan kata sandi yang terdaftar.</p>

            <form method="POST" action="{{ route('login.attempt') }}" novalidate data-auth-form>
                @csrf

                <div class="pk-field">
                    <label for="email">Email <span class="req">*</span></label>
                    <div class="pk-input {{ $errors->has('email') ? 'is-invalid' : '' }}">
                        <i data-lucide="mail" class="ic" width="17" height="17"></i>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
                    </div>
                    @error('email')<p class="pk-err" dusk="galat-email">{{ $message }}</p>@enderror
                </div>

                <div class="pk-field">
                    <label for="password">Kata sandi <span class="req">*</span></label>
                    <div class="pk-input has-toggle {{ $errors->has('password') ? 'is-invalid' : '' }}">
                        <i data-lucide="lock" class="ic" width="17" height="17"></i>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                        <button type="button" class="pk-eye" data-toggle-password="password" aria-label="Tampilkan kata sandi" dusk="tampilkan-sandi">
                            <i data-lucide="eye" class="ic ic-show" width="17" height="17"></i>
                            <i data-lucide="eye-off" class="ic ic-hide" width="17" height="17" hidden></i>
                        </button>
                    </div>
                    @error('password')<p class="pk-err" dusk="galat-password">{{ $message }}</p>@enderror
                </div>

                @error('credentials')
                    <div class="pk-alert" role="alert" dusk="galat-login">
                        <i data-lucide="circle-alert" class="ic" width="18" height="18"></i>
                        <div>{{ $message }}</div>
                    </div>
                @enderror

                <button type="submit" class="pk-btn pk-btn--p pk-btn--block" dusk="masuk">
                    <i data-lucide="log-in" class="ic" width="16" height="16"></i>Masuk
                </button>
            </form>

            <div class="pk-card-foot">
                Belum punya akun?
                <a href="{{ route('register', ['role' => 'umkm']) }}" dusk="daftar-umkm">Daftar sebagai UMKM</a> atau
                <a href="{{ route('register', ['role' => 'kandidat']) }}" dusk="daftar-kandidat">Daftar sebagai Kandidat</a>
                <small>Akun Admin disediakan oleh sistem dan tidak dapat didaftarkan.</small>
            </div>
        </div>
    </div>
</div>
@endsection
