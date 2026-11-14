{{--
    [PBI-14] Eksplor Proyek: daftar proyek yang dipublikasikan dan masih menerima lamaran.
    Variabel: $projects (paginator), $discovery (ProjectDiscoveryService).
    Pencarian/filter/urutan adalah PBI-15 dan belum ada di halaman ini.
--}}
@extends('layouts.app')
@section('title', 'Eksplor Proyek')

@push('styles')
<style>
    .pj-head{display:flex;align-items:center;gap:12px}
    .pj-head .meta{min-width:0}
    .pj-head .nm{font-weight:600;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .pj-head .loc{font-size:12.5px;color:var(--mut);display:flex;gap:4px;align-items:center;margin-top:2px}
    .pj-title{font-size:18px;font-weight:600;margin:14px 0 6px;letter-spacing:-.2px}
    .pj-title a{color:var(--ink)}
    .pj-title a:hover{color:var(--pri)}
    .pj-desc{color:var(--ink2);margin-bottom:12px}
    .pj-foot{display:flex;align-items:center;gap:20px;flex-wrap:wrap;margin-top:16px;padding-top:14px;border-top:1px solid var(--line2)}
    .pj-foot .it .l{font-size:12px;color:var(--mut)}
    .pj-foot .it .v{font-weight:600}
    .pj-foot .btn{margin-left:auto}
    .pj-pager{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:22px}
    @media (max-width:640px){.pj-foot .btn{margin-left:0;width:100%;justify-content:center}}
</style>
@endpush

@section('content')
    @php $rp = fn ($n) => 'Rp' . number_format((float) $n, 0, ',', '.'); @endphp

    <div class="ph">
        <div>
            <h1>Eksplor Proyek</h1>
            <p>Temukan proyek UMKM yang sedang membuka lamaran.</p>
        </div>
    </div>

    @forelse ($projects as $project)
        @php
            $umkm = $project->umkmProfile;
            $initials = collect(preg_split('/\s+/', trim($umkm->business_name)))->filter()->take(2)
                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
            $sisa = $discovery->remainingQuota($project, $project->accepted_count);
        @endphp
        <article class="card" dusk="kartu-proyek">
            <div class="pj-head">
                <div class="av">{{ $initials }}</div>
                <div class="meta">
                    <div class="nm">
                        {{ $umkm->business_name }}
                        @if ($umkm->isVerified())
                            <span class="bd ok"><i data-lucide="badge-check" class="ic" width="13" height="13"></i>UMKM Terverifikasi</span>
                        @endif
                    </div>
                    <div class="loc"><i data-lucide="map-pin" class="ic" width="13" height="13"></i>{{ $umkm->city }} · {{ $umkm->business_field }}</div>
                </div>
                <span class="bd info" style="margin-left:auto">{{ $project->category?->name }}</span>
            </div>

            <h3 class="pj-title"><a href="{{ route('kandidat.proyek.show', $project) }}">{{ $project->title }}</a></h3>
            <p class="pj-desc">{{ \Illuminate\Support\Str::limit($project->description, 170) }}</p>

            <div class="tags">
                @foreach ($project->skills as $skill)
                    <span class="tag">{{ $skill->name }}</span>
                @endforeach
            </div>

            <div class="pj-foot">
                <div class="it"><div class="l">Total kompensasi</div><div class="v num">{{ $rp($project->total_compensation) }} · {{ $project->payment_terms_count }} termin</div></div>
                <div class="it"><div class="l">Sisa kuota</div><div class="v">{{ $sisa }} dari {{ $project->quota }} orang</div></div>
                <div class="it"><div class="l">Tenggat lamaran</div><div class="v">{{ $project->application_deadline->locale('id')->translatedFormat('j M Y') }}</div></div>
                <a href="{{ route('kandidat.proyek.show', $project) }}" class="btn p" dusk="lihat-detail">Lihat Detail &amp; Lamar<i data-lucide="arrow-right" class="ic" width="16" height="16"></i></a>
            </div>
        </article>
    @empty
        <div class="card">
            <div class="al info">
                <i data-lucide="info" class="ic" width="19" height="19"></i>
                <div><b>Belum ada proyek yang terbuka</b>Saat ini belum ada proyek yang menerima lamaran. Silakan cek kembali nanti.</div>
            </div>
        </div>
    @endforelse

    @if ($projects->hasPages())
        <nav class="pj-pager" aria-label="Halaman">
            @if ($projects->onFirstPage())
                <span class="btn o sm dis">Sebelumnya</span>
            @else
                <a class="btn o sm" href="{{ $projects->previousPageUrl() }}">Sebelumnya</a>
            @endif
            <span class="sub">Halaman {{ $projects->currentPage() }} dari {{ $projects->lastPage() }}</span>
            @if ($projects->hasMorePages())
                <a class="btn o sm" href="{{ $projects->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="btn o sm dis">Berikutnya</span>
            @endif
        </nav>
    @endif
@endsection
