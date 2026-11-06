{{-- [PBI-14] Kartu Tentang UMKM di kolom kanan halaman Detail Proyek. Variabel: $project (umkmProfile.user sudah dimuat). --}}
@php
    $umkm = $project->umkmProfile;
    $initials = collect(preg_split('/\s+/', trim($umkm->business_name)))->filter()->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
    $rating = $umkm->averageRating();
    $ratingCount = $umkm->user?->receivedEvaluations()->count() ?? 0;
@endphp

<section class="card" dusk="kartu-umkm">
    <div class="ch"><i data-lucide="store" class="ic" width="20" height="20"></i><h3>Tentang UMKM</h3></div>

    <div class="pd-umkm">
        <div class="av">{{ $initials }}</div>
        <div>
            <div class="nm">{{ $umkm->business_name }}</div>
            @if ($umkm->isVerified())
                <span class="bd okb"><i data-lucide="badge-check" class="ic" width="13" height="13"></i>UMKM Terverifikasi</span>
            @endif
        </div>
    </div>

    <dl class="kv" style="grid-template-columns:110px 1fr">
        <dt>Bidang usaha</dt><dd>{{ $umkm->business_field }}</dd>
        <dt>Lokasi</dt><dd>{{ $umkm->city }}</dd>
        <dt>Penilaian</dt>
        <dd>
            @if ($rating !== null)
                @php $filled = (int) round($rating); @endphp
                <span class="star" aria-hidden="true">{!! str_repeat('★', $filled) !!}<span class="off">{!! str_repeat('★', 5 - $filled) !!}</span></span>
                {{ number_format($rating, 1, ',', '.') }} <span class="mut" style="font-weight:400">({{ $ratingCount }} evaluasi)</span>
            @else
                <span class="mut" style="font-weight:400">Belum ada penilaian</span>
            @endif
        </dd>
    </dl>
</section>
