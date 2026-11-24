{{--
    [PBI-26 / FR-12] Kesepakatan & Progres (UMKM), mockup 4.5.25 (bagian daftar dan ringkasan kesepakatan).
    Data dari AgreementController@umkmIndex / @umkmShow.

    Variabel: $tabs, $tab, $counts, $items (paginator), $detail (null bila belum ada yang dipilih)
    Tombol Buka: Draf -> halaman Susun Kesepakatan (PBI-24); status lain -> detail di bawah tabel.
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
            <p>Pantau kesepakatan pekerjaan dengan kandidat, mulai dari draf hingga selesai.</p>
        </div>
    </div>

    <section class="card tcard" dusk="daftar-kesepakatan">
        <nav class="tabs" dusk="tab-kesepakatan" aria-label="Status kesepakatan">
            @foreach ($tabs as $key => [$label])
                <a href="{{ route('umkm.kesepakatan.index', ['status' => $key]) }}" class="{{ $tab === $key ? 'on' : '' }}" dusk="tab-{{ $key }}" @if ($tab === $key) aria-current="page" @endif>
                    {{ $label }} <span>{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($items->isEmpty())
            <div class="empty" dusk="kosong">
                <i data-lucide="handshake" class="ic" width="32" height="32"></i>
                <div class="b mt8">Belum ada kesepakatan {{ mb_strtolower($tabs[$tab][0]) }}</div>
                <div class="sub mt8">Kesepakatan disusun dari halaman Detail Pelamar setelah pelamar diterima.</div>
            </div>
        @else
            <div class="tscroll">
                <table class="t ks-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kandidat &amp; Proyek</th>
                            <th>Progres</th>
                            <th>Tenggat</th>
                            <th>Status</th>
                            <th><span class="sr">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php
                                $progress = AgreementPresenter::latestProgress($item);
                                $isSelected = $detail && $detail['agreement']->id === $item->id;
                                $openUrl = $item->status === AgreementStatus::Draft
                                    ? route('umkm.kesepakatan.edit', $item)
                                    : route('umkm.kesepakatan.show', $item).'#detail-kesepakatan';
                            @endphp
                            <tr dusk="baris-kesepakatan-{{ $item->id }}">
                                <td class="b nw">{{ AgreementPresenter::code($item) }}</td>
                                <td>
                                    <div class="nm">{{ $item->application->candidateProfile?->full_name }}</div>
                                    <div class="sub">{{ $item->application->project?->title }}</div>
                                </td>
                                <td>
                                    @if ($progress !== null)
                                        <div class="prow"><div class="prog"><i style="width:{{ $progress }}%"></i></div><span class="small num">{{ $progress }}%</span></div>
                                    @else
                                        <span class="sub">-</span>
                                    @endif
                                </td>
                                <td class="nw">{{ SelectionPresenter::date($item->deadline) }}</td>
                                <td><span class="bd {{ $item->status->badge() }}">{{ $item->status->label() }}</span></td>
                                <td class="ar">
                                    <a href="{{ $openUrl }}" class="btn {{ $isSelected ? 'ob' : 'o' }} sm" dusk="buka-kesepakatan-{{ $item->id }}">Buka</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
        @include('kesepakatan._detail', ['detail' => $detail, 'role' => 'umkm'])
    @endif
@endsection
