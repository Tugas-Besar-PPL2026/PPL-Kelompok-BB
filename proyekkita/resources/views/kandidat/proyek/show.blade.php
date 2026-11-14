{{--
    Halaman Detail Proyek & Ajukan Lamaran (satu halaman untuk dua PBI):
      - PBI-14: kolom kiri (_detail) dan kartu Tentang UMKM (_umkm)
      - PBI-16: kartu Ajukan Lamaran (_lamar)
    Variabel: $project, $outputs, $deadlineNote, $remainingQuota, $apply.
--}}
@extends('layouts.app')
@section('title', $project->title)

@push('styles')
<style>
    /* ----- PBI-14: detail proyek ----- */
    .pd-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;color:var(--mut);font-size:13.5px}
    .pd-title{font-size:26px;font-weight:700;letter-spacing:-.4px;line-height:1.25;margin:12px 0 14px}
    .pd-text{color:var(--ink2);font-size:15px;line-height:1.6;white-space:pre-line}
    .pd-h{font-size:15px;font-weight:600;margin:22px 0 10px}
    .pd-list{margin:0;padding-left:20px;color:var(--ink2);line-height:1.7}
    .pd-stats{margin-top:22px}
    .pd-comp{display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:26px 0 10px}
    .pd-comp .total{font-size:24px;font-weight:700;letter-spacing:-.3px}
    .tbl-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:10px}
    .tbl-wrap table.t{min-width:520px}
    .tbl-wrap tr.sum td{font-weight:600;background:#FBFBFE}
    .pd-umkm{display:flex;gap:14px;align-items:center;margin-bottom:14px}
    .pd-umkm .av{width:52px;height:52px;font-size:18px;border-radius:10px}
    .pd-umkm .nm{font-size:17px;font-weight:600;margin-bottom:4px}
    .star{color:#F59E0B;letter-spacing:1px}.star .off{color:#D7D9E4}

    /* ----- PBI-16: kartu Ajukan Lamaran ----- */
    .ap-checks{list-style:none;display:grid;gap:8px;margin-bottom:18px}
    .ap-checks li{display:flex;align-items:center;gap:9px;font-size:13.5px}
    .ap-checks .ok{color:var(--ok)}.ap-checks .no{color:var(--bad)}
    .ap-attach{border:1px solid var(--line);border-radius:10px;background:#FBFBFE;padding:14px 16px;margin:16px 0}
    .ap-attach .ttl{font-weight:600;font-size:13px;margin-bottom:10px}
    .ap-attach .it{display:flex;align-items:center;gap:10px;padding:5px 0;font-size:13.5px}
    .ap-attach .it .st{margin-left:auto;display:flex;align-items:center;gap:8px}
    .ap-attach .it .mark.ok{color:var(--ok)}.ap-attach .it .mark.no{color:var(--bad)}.ap-attach .it .mark.na{color:#9A9EAD}
    .ap-counter{display:flex;justify-content:space-between;gap:10px;margin-top:2px}
    @media (max-width:640px){.pd-title{font-size:22px}.ap-attach .it{flex-wrap:wrap}.ap-attach .it .st{margin-left:28px}}
</style>
@endpush

@section('content')
    <div class="crumb">
        <a href="{{ route('kandidat.proyek.index') }}">Eksplor Proyek</a>
        <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
        <span>{{ $project->title }}</span>
    </div>

    <div class="grid g21" style="align-items:start">
        <div>@include('kandidat.proyek._detail')</div>
        <div>
            @include('kandidat.proyek._lamar')
            @include('kandidat.proyek._umkm')
        </div>
    </div>
@endsection
