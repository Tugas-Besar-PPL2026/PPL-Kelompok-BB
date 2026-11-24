{{--
    [PBI-26 / FR-12] Kesepakatan & Progres (Kandidat), mockup 4.5.24 (bagian daftar dan ringkasan kesepakatan).
    Data dari AgreementController@candidateIndex / @candidateShow. Draf tidak pernah tampil di sini.

    Variabel: $tabs, $tab, $counts, $items (paginator), $detail (null bila belum ada yang dipilih)
    Kartu "Perlu Tanggapan" membuka halaman Tanggapi Kesepakatan (PBI-25); kartu lain membuka detail di bawah.
--}}
@extends('layouts.app')
@section('title', 'Kesepakatan & Progres')

@use('App\Enums\AgreementStatus')
@use('App\Support\AgreementPresenter')
@use('App\Support\SelectionPresenter')
@include('kesepakatan._gaya')

@section('content')
    <div class="ph">
        <div>
            <h1>Kesepakatan &amp; Progres</h1>
            <p>Pantau kesepakatan pekerjaan dan tanggapi tawaran kesepakatan dari UMKM.</p>
        </div>
    </div>

    <section class="card tcard" dusk="daftar-kesepakatan">
        <nav class="tabs" dusk="tab-kesepakatan" aria-label="Status kesepakatan">
            @foreach ($tabs as $key => [$label])
                <a href="{{ route('kandidat.kesepakatan.index', ['status' => $key]) }}" class="{{ $tab === $key ? 'on' : '' }}" dusk="tab-{{ $key }}" @if ($tab === $key) aria-current="page" @endif>
                    {{ $label }} <span>{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($items->isEmpty())
            <div class="empty" dusk="kosong">
                <i data-lucide="handshake" class="ic" width="32" height="32"></i>
                <div class="b mt8">Belum ada kesepakatan {{ mb_strtolower($tabs[$tab][0]) }}</div>
                <div class="sub mt8">Kesepakatan muncul di sini setelah UMKM mengirimkannya kepada Anda.</div>
            </div>
        @else
            <div class="ks-cards">
                @foreach ($items as $item)
                    @php
                        $progress = AgreementPresenter::latestProgress($item);
                        $isSelected = $detail && $detail['agreement']->id === $item->id;
                        $openUrl = $item->status === AgreementStatus::Sent
                            ? route('kandidat.kesepakatan.tanggapi', $item)
                            : route('kandidat.kesepakatan.show', $item).'#detail-kesepakatan';
                    @endphp
                    <a href="{{ $openUrl }}" class="ks-card {{ $isSelected ? 'on' : '' }}" dusk="kartu-kesepakatan-{{ $item->id }}">
                        <div class="t">
                            <span class="nm">{{ $item->application->project?->title }}</span>
                            <span class="bd {{ $item->status->badge() }}">{{ $item->status->label() }}</span>
                        </div>
                        <div class="sub mt8">{{ $item->application->project?->umkmProfile?->business_name }} · tenggat {{ SelectionPresenter::date($item->deadline) }}</div>
                        @if ($progress !== null)
                            <div class="prow"><div class="prog"><i style="width:{{ $progress }}%"></i></div><span class="small num">{{ $progress }}%</span></div>
                        @endif
                    </a>
                @endforeach
            </div>

            @if ($items->hasPages())
                <div class="pager">
                    <span>Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }} · {{ $items->total() }} kesepakatan</span>
                    <span class="row">
                        @if ($items->onFirstPage())
                            <span class="btn o sm dis">Sebelumnya</span>
                        @else
                            <a href="{{ $items->previousPageUrl() }}" class="btn o sm">Sebelumnya</a>
                        @endif
                        @if ($items->hasMorePages())
                            <a href="{{ $items->nextPageUrl() }}" class="btn o sm">Berikutnya</a>
                        @else
                            <span class="btn o sm dis">Berikutnya</span>
                        @endif
                    </span>
                </div>
            @endif
        @endif
    </section>

    @if ($detail)
        @include('kesepakatan._detail', ['detail' => $detail, 'role' => 'kandidat'])
    @endif
@endsection
