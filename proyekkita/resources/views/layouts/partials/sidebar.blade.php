@php
    $allMenus = [
        'umkm' => [
            ['Profil Usaha', 'store', 'umkm.profil.edit', 'umkm.profil.*'],
            ['Proyek Saya', 'briefcase', 'umkm.proyek.index', 'umkm.proyek.*'],
            ['Seleksi Kandidat', 'users', 'umkm.seleksi.index', 'umkm.seleksi.*'],
            ['Kesepakatan & Progres', 'handshake', 'umkm.kesepakatan.index', 'umkm.kesepakatan.*'],
            ['Pembayaran', 'wallet', 'umkm.pembayaran.index', 'umkm.pembayaran.*'],
            ['Evaluasi', 'star', 'umkm.evaluasi.index', 'umkm.evaluasi.*'],
            ['Notifikasi', 'bell', 'notifikasi.index', 'notifikasi.*'],
        ],

        'kandidat' => [
            ['Profil & Portofolio', 'user-round', 'kandidat.profil.edit', 'kandidat.profil.*'],
            ['Eksplor Proyek', 'search', 'kandidat.proyek.index', 'kandidat.proyek.*'],
            ['Lamaran Saya', 'send', 'kandidat.lamaran.index', 'kandidat.lamaran.*'],
            ['Kesepakatan & Progres', 'handshake', 'kandidat.kesepakatan.index', 'kandidat.kesepakatan.*'],
            ['Pembayaran', 'wallet', 'kandidat.pembayaran.index', 'kandidat.pembayaran.*'],
            ['Riwayat & Evaluasi', 'award', 'kandidat.riwayat.index', 'kandidat.riwayat.*'],
            ['Notifikasi', 'bell', 'notifikasi.index', 'notifikasi.*'],
        ],

        'admin' => [
            ['Data Master', 'database', 'admin.master.index', 'admin.master.*'],
            ['Verifikasi UMKM', 'badge-check', 'admin.verifikasi.index', 'admin.verifikasi.*'],
            ['Moderasi Proyek', 'shield-check', 'admin.moderasi.index', 'admin.moderasi.*'],
            ['Notifikasi', 'bell', 'notifikasi.index', 'notifikasi.*'],
        ],
    ];

    $menus = $allMenus[$authUser->role->value] ?? [];

    $verification = $authUser->isUmkm()
        ? $authUser->umkmProfile?->verification_status
        : null;
@endphp

<aside class="side">

    {{-- INFORMASI PENGGUNA --}}
    <div class="sidehead">
        <div class="av">{{ $navInitials }}</div>

        <div>
            <div class="n">{{ $navName }}</div>

            <div style="margin-top:3px">
                @if ($verification)
                    <span class="bd {{ $verification->badge() }}">
                        <i
                            data-lucide="{{ $verification->value === 'verified' ? 'badge-check' : 'clock' }}"
                            class="ic"
                            width="13"
                            height="13">
                        </i>
                        {{ $verification->label() }}
                    </span>
                @else
                    <span class="sub">
                        {{ $authUser->isAdmin() ? 'Administrator' : $authUser->role->label() }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- MENU --}}
    @foreach ($menus as [$label, $icon, $routeName, $activePattern])

        <a
            href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
            class="mi {{ request()->routeIs($activePattern) ? 'on' : '' }}"
        >
            <i data-lucide="{{ $icon }}" class="ic" width="19" height="19"></i>

            <span>{{ $label }}</span>

            @if ($routeName === 'notifikasi.index' && $navUnread > 0)
                <span class="cnt">{{ $navUnread }}</span>
            @endif
        </a>

    @endforeach

    {{-- LOGOUT --}}
    <div class="sidefoot">

        @if (Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="mi danger">
                    <i data-lucide="log-out" class="ic" width="19" height="19"></i>
                    <span>Keluar</span>
                </button>
            </form>
        @endif

    </div>

</aside>