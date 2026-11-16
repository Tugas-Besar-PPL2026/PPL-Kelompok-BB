{{-- Panel biru di kiri halaman Masuk / Registrasi. Variabel: $title, $lead, $points (array teks). Disembunyikan di layar kecil. --}}
<aside class="pk-aside">
    <div class="pk-aside-logo"><i data-lucide="network" class="ic" width="26" height="26"></i>ProyekKita</div>
    <h1>{{ $title }}</h1>
    <p class="pk-lead">{{ $lead }}</p>
    <ul class="pk-points">
        @foreach ($points as $point)
            <li><i data-lucide="circle-check" class="ic" width="18" height="18"></i><span>{{ $point }}</span></li>
        @endforeach
    </ul>
</aside>
