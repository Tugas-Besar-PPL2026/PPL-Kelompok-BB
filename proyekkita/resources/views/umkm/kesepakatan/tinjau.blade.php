{{--
    [PBI-32 / FR-15] Halaman Tinjau Hasil Pekerjaan (UMKM), Figma frame 25.
    Variabel (SubmissionReviewService::page): $agreement, $project, $code, $candidateName, $stages, $latest, $latestModel,
    $history, $revision, $decision.
    Ketentuan 1 berkas per versi (keputusan K-1 PBI-31): panel versi menampilkan paling banyak satu berkas.
--}}
@extends('layouts.app')
@section('title', 'Tinjau Hasil Pekerjaan')

@push('styles')
@include('kesepakatan._gaya')
@endpush

@section('content')
    <div dusk="halaman-tinjau">
        <div class="crumb">
            <span>Kesepakatan &amp; Progres</span>
            <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
            <span>{{ $code }}</span>
            <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
            <span>Tinjau Hasil</span>
        </div>
        <div class="ph">
            <div>
                <h1>Tinjau Hasil Pekerjaan</h1>
                <p>Periksa hasil yang diserahkan, lalu setujui atau minta revisi sesuai batas pada kesepakatan.</p>
            </div>
        </div>

        <section class="card" dusk="ringkasan-tinjau">
            <div class="ps-head">
                <span class="ps-code">{{ $code }}</span>
                <span class="bd {{ $agreement->status->badge() }}" dusk="status-kesepakatan">{{ $agreement->status->label() }}</span>
                @if ($latest)
                    <span class="bd {{ $latest['statusBadge'] }}" dusk="status-versi-terbaru">Versi {{ $latest['version'] }} · {{ $latest['statusLabel'] }}</span>
                @endif
            </div>
            <h2 class="ps-title" dusk="judul-proyek">{{ $project?->title }}</h2>
            <div class="sub">bersama {{ $candidateName }}</div>
            @include('kesepakatan._tahapan')
        </section>

        <div class="grid g21 ps-grid">
            <div class="tr-stack">
                @include('umkm.kesepakatan._versi-terbaru')
                @include('umkm.kesepakatan._riwayat-tabel')
            </div>
            <div>
                @include('umkm.kesepakatan._keputusan')
            </div>
        </div>
    </div>
@endsection
