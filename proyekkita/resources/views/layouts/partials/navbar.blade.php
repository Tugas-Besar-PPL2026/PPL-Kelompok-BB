{{-- Navbar atas: logo, lonceng notifikasi, dan identitas pengguna yang sedang login. --}}
<header class="top">
    <a href="{{ url('/') }}" class="logo">
        <i data-lucide="network" class="ic" width="26" height="26"></i>ProyekKita
    </a>

    <div class="tright">
        <a href="{{ Route::has('notifikasi.index') ? route('notifikasi.index') : '#' }}" class="bell" title="Notifikasi">
            <i data-lucide="bell" class="ic" width="21" height="21"></i>
            @if ($navUnread > 0)
                <b>{{ $navUnread > 99 ? '99+' : $navUnread }}</b>
            @endif
        </a>

        <div class="uchip">
            <div class="av">{{ $navInitials }}</div>
            <div>
                <div class="n">{{ $navName }}</div>
                <div class="r">{{ $navRole }}</div>
            </div>
        </div>
    </div>
</header>
