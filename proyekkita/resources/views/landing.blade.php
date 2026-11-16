{{-- [FR-01] Landing Page (Beranda Publik). Hanya untuk tamu; pengguna yang sudah login diarahkan ke halaman awal perannya. --}}
@extends('layouts.guest')

@section('title', 'Beranda')
@section('nav_active', 'cara-kerja')

@section('actions')
    <a href="{{ route('login') }}" class="pk-btn pk-btn--g" dusk="nav-masuk">Masuk</a>
    <a href="{{ route('register', ['role' => 'umkm']) }}" class="pk-btn pk-btn--p" dusk="nav-daftar">Daftar</a>
@endsection

@section('content')
<main class="pk-wrap" style="flex:1">
    {{-- Hero --}}
    <section class="pk-hero">
        <div>
            <span class="pk-chip">
                <i data-lucide="sprout" class="ic" width="13" height="13"></i>Mendukung SDG 8 · Pekerjaan Layak &amp; Pertumbuhan Ekonomi
            </span>
            <h1>Proyek jangka pendek UMKM, dikerjakan <span class="hl">mahasiswa &amp; lulusan baru</span>.</h1>
            <p class="pk-lead">
                ProyekKita mempertemukan UMKM yang membutuhkan bantuan pekerjaan tertentu dengan kandidat yang ingin
                memperoleh pengalaman kerja nyata, mulai dari publikasi proyek hingga pembayaran dan evaluasi tercatat
                dalam satu platform.
            </p>
            <div class="pk-cta">
                <a href="{{ route('register', ['role' => 'umkm']) }}" class="pk-btn pk-btn--p" dusk="hero-daftar-umkm">
                    <i data-lucide="store" class="ic" width="16" height="16"></i>Daftar sebagai UMKM
                </a>
                <a href="{{ route('register', ['role' => 'kandidat']) }}" class="pk-btn pk-btn--o" dusk="hero-daftar-kandidat">
                    <i data-lucide="graduation-cap" class="ic" width="16" height="16"></i>Daftar sebagai Kandidat
                </a>
            </div>
            <p class="pk-have">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div>

        {{-- Contoh tampilan proyek (ilustrasi statis sesuai desain, bukan data sebenarnya). --}}
        <div class="pk-demo" aria-hidden="true">
            <div class="pk-demo-card">
                <div class="pk-demo-meta">
                    <b>Kopi Nusantara</b>
                    <span class="pk-badge-ok"><i data-lucide="badge-check" class="ic" width="13" height="13"></i>UMKM Terverifikasi</span>
                    <span class="city">• Bandung</span>
                </div>
                <h3>Redesain Katalog Digital &amp; Label Kemasan</h3>
                <p>Kopi Nusantara membutuhkan desainer untuk memperbarui label kemasan tiga varian biji kopi (Arabika Gayo, Robusta Temangg…</p>
                <div class="pk-tags"><span>Figma</span><span>Desain Grafis</span><span>Desain Kemasan</span></div>
                <div class="pk-demo-foot">
                    <div>
                        <small>Total kompensasi</small>
                        <strong>Rp2.500.000</strong>
                        <small>2 termin</small>
                    </div>
                    <div class="r">
                        <small>Tenggat lamaran</small>
                        <strong>20 Nov 2026</strong>
                        <small>kuota 2 kandidat</small>
                    </div>
                </div>
            </div>
            <div class="pk-toast">
                <i data-lucide="circle-check" class="ic" width="22" height="22"></i>
                <div><b>Termin 1 dikonfirmasi diterima</b><span>Rp1.250.000 · dicatat oleh UMKM</span></div>
            </div>
        </div>
    </section>

    {{-- Cara kerja --}}
    <section class="pk-sec" id="cara-kerja">
        <h2>Cara kerja ProyekKita</h2>
        <p class="pk-sub">Lima tahap dari proyek dipublikasikan hingga pekerjaan selesai.</p>
        <div class="pk-steps">
            @foreach ([
                ['file-plus-2', 'Ajukan proyek', 'UMKM terverifikasi mengisi detail proyek, lalu admin memeriksa kelayakannya sebelum dipublikasikan.'],
                ['search', 'Cari & lamar', 'Kandidat mencari proyek sesuai keterampilan dan melamar dengan profil serta portofolio.'],
                ['handshake', 'Seleksi & kesepakatan', 'UMKM menyeleksi pelamar sesuai kuota, lalu kedua pihak menyetujui kesepakatan pekerjaan.'],
                ['hammer', 'Pengerjaan & revisi', 'Kandidat memperbarui progres dan menyerahkan hasil; UMKM meninjau atau meminta revisi.'],
                ['star', 'Pembayaran & evaluasi', 'UMKM mencatat pembayaran per termin, kandidat mengonfirmasi, lalu keduanya saling menilai.'],
            ] as $i => [$icon, $title, $text])
                <article class="pk-step">
                    <div class="pk-step-top">
                        <span class="pk-num">{{ $i + 1 }}</span>
                        <i data-lucide="{{ $icon }}" class="ic" width="22" height="22"></i>
                    </div>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Untuk UMKM / Untuk Kandidat --}}
    <section class="pk-panels">
        <article class="pk-panel" id="untuk-umkm">
            <div class="pk-panel-head">
                <span class="pk-panel-ico"><i data-lucide="store" class="ic" width="28" height="28"></i></span>
                <div>
                    <h3>Untuk UMKM</h3>
                    <p>Selesaikan kebutuhan proyek tanpa merekrut karyawan tetap</p>
                </div>
            </div>
            <ul class="pk-list">
                @foreach ([
                    'Publikasikan proyek dengan ruang lingkup, tenggat, dan kompensasi yang jelas',
                    'Seleksi pelamar berdasarkan profil, keterampilan, pengalaman, dan portofolio',
                    'Pantau progres, tinjau hasil, dan minta revisi sesuai batas kesepakatan',
                    'Catat pembayaran per termin beserta bukti transfer',
                ] as $item)
                    <li><i data-lucide="circle-check" class="ic" width="18" height="18"></i><span>{{ $item }}</span></li>
                @endforeach
            </ul>
            <a href="{{ route('register', ['role' => 'umkm']) }}" class="pk-btn pk-btn--p" dusk="panel-daftar-umkm">
                <i data-lucide="arrow-right" class="ic" width="16" height="16"></i>Daftar sebagai UMKM
            </a>
        </article>

        <article class="pk-panel" id="untuk-kandidat">
            <div class="pk-panel-head">
                <span class="pk-panel-ico"><i data-lucide="graduation-cap" class="ic" width="28" height="28"></i></span>
                <div>
                    <h3>Untuk Mahasiswa &amp; Lulusan Baru</h3>
                    <p>Dapatkan pengalaman kerja nyata dan kompensasi yang disepakati</p>
                </div>
            </div>
            <ul class="pk-list">
                @foreach ([
                    'Cari dan saring proyek berdasarkan kategori, keterampilan, kompensasi, dan tenggat',
                    'Kesepakatan pekerjaan tercatat sebelum mulai bekerja',
                    'Konfirmasi setiap pembayaran yang Anda terima',
                    'Riwayat proyek dan penilaian UMKM menjadi rekam pengalaman kerja',
                ] as $item)
                    <li><i data-lucide="circle-check" class="ic" width="18" height="18"></i><span>{{ $item }}</span></li>
                @endforeach
            </ul>
            <a href="{{ route('register', ['role' => 'kandidat']) }}" class="pk-btn pk-btn--o" dusk="panel-daftar-kandidat">
                <i data-lucide="arrow-right" class="ic" width="16" height="16"></i>Daftar sebagai Kandidat
            </a>
        </article>
    </section>

    {{-- Jaminan platform --}}
    <section class="pk-trust">
        @foreach ([
            ['badge-check', 'UMKM diverifikasi', 'Admin memeriksa identitas dan legalitas usaha sebelum UMKM dapat mengajukan proyek.'],
            ['shield-check', 'Proyek dimoderasi', 'Setiap proyek diperiksa kelayakannya oleh admin sebelum tampil kepada kandidat.'],
            ['circle-dollar-sign', 'Pembayaran tercatat', 'Pembayaran dilakukan di luar aplikasi, lalu dicatat per termin dan dikonfirmasi kandidat.'],
        ] as [$icon, $title, $text])
            <div class="pk-trust-i">
                <span class="pk-trust-ico"><i data-lucide="{{ $icon }}" class="ic" width="20" height="20"></i></span>
                <div><b>{{ $title }}</b><p>{{ $text }}</p></div>
            </div>
        @endforeach
    </section>
</main>

<footer class="pk-foot">
    <div class="pk-foot-in">
        <div class="pk-foot-l">
            <span class="pk-foot-logo"><i data-lucide="network" class="ic" width="20" height="20"></i>ProyekKita</span>
            <span>Platform proyek kerja jangka pendek UMKM dan mahasiswa/lulusan baru</span>
        </div>
        <span>© 2026 ProyekKita · Kelompok B-B PPL</span>
    </div>
</footer>
@endsection
