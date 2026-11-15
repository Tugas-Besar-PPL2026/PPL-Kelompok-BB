{{--
    [PBI-17 / FR-10] Lamaran Saya: daftar lamaran milik kandidat yang login, dengan tab filter status.
    Variabel: $status (?ApplicationStatus), $counts, $tabs, $items (paginator hasil CandidateApplicationService).
--}}
@extends('layouts.app')
@section('title', 'Lamaran Saya')

@push('styles')
<style>
    .la-tabs{overflow-x:auto}
    .la-tabs a{white-space:nowrap}
    .la-card{margin-bottom:18px}
    .la-top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
    .la-main{min-width:0}
    .la-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .la-title{font-size:17px;font-weight:600;margin:12px 0 2px;letter-spacing:-.2px}
    .la-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}
    .la-note{margin-top:14px;max-width:760px;padding:12px 14px;border:1px solid var(--line);border-radius:8px;background:#FBFBFE;font-size:13.5px;white-space:pre-line}
    .la-hint{margin-top:12px}
    .la-hist{margin-top:16px}
    .la-hist summary{cursor:pointer;list-style:none;display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--mut)}
    .la-hist summary::-webkit-details-marker{display:none}
    .la-hist summary::before{content:"\25B8";font-size:11px;transition:transform .15s}
    .la-hist[open] summary::before{transform:rotate(90deg)}
    .la-hist .tl{margin-top:14px}
    .la-hist .t3{white-space:pre-line}
    .la-pager{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:22px}
    @media (max-width:760px){
        .la-top{flex-direction:column}
        .la-actions{justify-content:flex-start}
    }
</style>
@endpush

@section('content')
    <div class="ph">
        <div>
            <h1>Lamaran Saya</h1>
            <p>Pantau status setiap lamaran, catatan seleksi dari UMKM, dan riwayat perubahan statusnya.</p>
        </div>
    </div>

    <nav class="tabs la-tabs" aria-label="Filter status lamaran">
        @foreach ($tabs as $tab)
            <a href="{{ route('kandidat.lamaran.index', $tab['key'] === '' ? [] : ['status' => $tab['key']]) }}"
               class="{{ ($status?->value ?? '') === $tab['key'] ? 'on' : '' }}">{{ $tab['label'] }}<span>{{ $tab['count'] }}</span></a>
        @endforeach
    </nav>

    <div class="al info mb16">
        <i data-lucide="info" class="ic" width="19" height="19"></i>
        <div>Lamaran hanya dapat dibatalkan selama berstatus <strong>Menunggu Keputusan</strong> atau <strong>Terpilih Sementara</strong>.</div>
    </div>

    @forelse ($items as $item)
        @include('kandidat.lamaran._kartu', ['item' => $item])
    @empty
        <div class="card">
            @if ($counts['all'] === 0)
                <div class="al info">
                    <i data-lucide="send" class="ic" width="19" height="19"></i>
                    <div>
                        <b>Belum ada lamaran</b>
                        Anda belum mengajukan lamaran pada proyek mana pun.
                        @if (Route::has('kandidat.proyek.index'))
                            <a href="{{ route('kandidat.proyek.index') }}" style="font-weight:600">Eksplor proyek</a>
                        @endif
                    </div>
                </div>
            @else
                <div class="al info">
                    <i data-lucide="filter" class="ic" width="19" height="19"></i>
                    <div>
                        <b>Tidak ada lamaran dengan status {{ $status?->label() }}</b>
                        <a href="{{ route('kandidat.lamaran.index') }}" style="font-weight:600">Lihat semua lamaran</a>
                    </div>
                </div>
            @endif
        </div>
    @endforelse

    @if ($items->hasPages())
        <nav class="la-pager" aria-label="Halaman">
            @if ($items->onFirstPage())
                <span class="btn o sm dis">Sebelumnya</span>
            @else
                <a class="btn o sm" href="{{ $items->previousPageUrl() }}">Sebelumnya</a>
            @endif
            <span class="sub">Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}</span>
            @if ($items->hasMorePages())
                <a class="btn o sm" href="{{ $items->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="btn o sm dis">Berikutnya</span>
            @endif
        </nav>
    @endif
@endsection
