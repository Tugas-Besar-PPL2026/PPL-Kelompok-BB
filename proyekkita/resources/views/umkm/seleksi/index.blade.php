{{--
    Seleksi Kandidat: daftar pelamar satu proyek (PBI-19, mockup 4.5.19).
    Data dari CandidateSelectionController@index: $projects, $project, $status, $counts, $tabs, $quota, $since, $applicants.
--}}
@extends('layouts.app')
@section('title', 'Seleksi Kandidat')

@use('App\Support\SelectionPresenter', 'P')

@section('content')
    <div class="ph">
        <div class="hd">
            <h1>Seleksi Kandidat</h1>
            <p>Tinjau pelamar, masukkan ke daftar terpilih sementara, lalu terima atau tolak sesuai kuota proyek.</p>
        </div>

        @if ($project)
            <form method="GET" action="{{ route('umkm.seleksi.index') }}" class="act projsel-form">
                <label class="projsel">
                    <span>Proyek:</span>
                    <select name="proyek" aria-label="Pilih proyek" onchange="this.form.submit()">
                        @foreach ($projects as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $project->id)>{{ $option->title }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="ic" width="17" height="17"></i>
                </label>
                <noscript><button type="submit" class="btn o sm">Tampilkan</button></noscript>
            </form>
        @endif
    </div>

    @if (! $project)
        <section class="card">
            <div class="empty">
                <i data-lucide="users" class="ic" width="34" height="34"></i>
                <b>Belum ada proyek yang dapat menerima pelamar</b>
                <span>Pelamar akan muncul di sini setelah proyek Anda dipublikasikan.</span>
                <a href="{{ route('umkm.proyek.create') }}" class="btn p mt12"><i data-lucide="plus" class="ic" width="16" height="16"></i>Buat Proyek</a>
            </div>
        </section>
    @else
        <div class="grid g4 stats">
            <div class="stat">
                <div class="l">Total pelamar</div>
                <div class="v num">{{ $counts['all'] }}</div>
                <div class="s">{{ $since }}</div>
            </div>
            <div class="stat">
                <div class="l">Menunggu keputusan</div>
                <div class="v num">{{ $counts['pending'] }}</div>
                <div class="s">perlu ditinjau</div>
            </div>
            <div class="stat">
                <div class="l">Terpilih sementara</div>
                <div class="v num">{{ $counts['shortlisted'] }}</div>
                <div class="s">daftar pendek</div>
            </div>
            <div class="stat">
                <div class="l">Diterima</div>
                <div class="v num">{{ $quota['accepted'] }} / {{ $quota['total'] }}</div>
                <div class="s">kuota kandidat</div>
            </div>
        </div>

        <section class="card quota">
            <div class="between">
                <div class="t"><i data-lucide="user-check" class="ic" width="20" height="20"></i>Kuota kandidat terisi {{ $quota['accepted'] }} dari {{ $quota['total'] }}</div>
                <div class="sub">
                    @if ($quota['full'])
                        Kuota terpenuhi
                    @else
                        Sisa {{ $quota['remaining'] }} kandidat dapat diterima
                    @endif
                </div>
            </div>
            <div class="prog" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $quota['percent'] }}" aria-label="Kuota kandidat terisi">
                <i style="width:{{ $quota['percent'] }}%"></i>
            </div>
        </section>

        <section class="card tcard">
            <nav class="tabs scroll" aria-label="Filter status pelamar">
                @foreach ($tabs as $tab)
                    @php $active = ($status?->value ?? '') === $tab['key']; @endphp
                    <a href="{{ route('umkm.seleksi.index', array_filter(['proyek' => $project->id, 'status' => $tab['key']])) }}"
                       class="{{ $active ? 'on' : '' }}" @if ($active) aria-current="page" @endif>
                        {{ $tab['label'] }} <span>{{ $tab['count'] }}</span>
                    </a>
                @endforeach
            </nav>

            @if ($applicants->isEmpty())
                <div class="empty">
                    <i data-lucide="inbox" class="ic" width="34" height="34"></i>
                    @if ($status)
                        <b>Tidak ada pelamar berstatus {{ $status->label() }}</b>
                        <span>Pilih tab lain untuk melihat pelamar dengan status berbeda.</span>
                    @else
                        <b>Belum ada pelamar pada proyek ini</b>
                        <span>Pelamar akan tampil di sini begitu ada kandidat yang mengirim lamaran.</span>
                    @endif
                </div>
            @else
                <div class="tscroll">
                    <table class="t pel">
                        <thead>
                            <tr>
                                <th>Pelamar</th>
                                <th class="c-skill">Keterampilan</th>
                                <th class="c-exp">Pengalaman</th>
                                <th class="c-rate">Penilaian</th>
                                <th class="c-date">Dilamar</th>
                                <th class="c-status">Status</th>
                                <th class="r c-act">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($applicants as $row)
                                @php
                                    $application = $row['application'];
                                    $profile = $row['profile'];
                                    $stats = $row['stats'];
                                @endphp
                                <tr>
                                    <td>
                                        <div class="pl">
                                            @include('umkm.seleksi._avatar', ['profile' => $profile])
                                            <div>
                                                <div class="n">{{ $profile->full_name }}</div>
                                                <div class="s">{{ $profile->university }}</div>
                                                <div class="s">{{ $profile->city }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tags cell">
                                            @forelse ($row['skills'] as $skill)
                                                <span class="tag sm">{{ $skill->name }}</span>
                                            @empty
                                                <span class="sub">Belum ada keterampilan</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="celltxt">
                                        @if ($row['experience']['count'] === 0)
                                            <span class="mut">Belum ada pengalaman</span>
                                        @else
                                            <div>{{ $row['experience']['count'] }} pengalaman</div>
                                            @if ($row['experience']['duration'])
                                                <div>{{ $row['experience']['duration'] }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="celltxt">
                                        @if ($stats['rating'] !== null)
                                            <div class="rt"><i data-lucide="star" class="ic star" width="15" height="15"></i>{{ P::decimal($stats['rating']) }}</div>
                                            <div class="mut">{{ $stats['completed'] }} proyek selesai</div>
                                        @else
                                            <div class="mut">Belum ada penilaian</div>
                                            @if ($stats['completed'] > 0)
                                                <div class="mut">{{ $stats['completed'] }} proyek selesai</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="num c-date">{{ P::date($application->created_at) }}</td>
                                    <td><span class="bd {{ $application->status->badge() }}">{{ $application->status->label() }}</span></td>
                                    <td class="r">
                                        <a href="{{ route('umkm.seleksi.show', array_filter(['application' => $application->id, 'status' => $status?->value])) }}" class="btn o sm" aria-label="Lihat detail pelamar {{ $profile->full_name }}">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $applicants->links('umkm.seleksi._pagination') }}
            @endif
        </section>
    @endif
@endsection
