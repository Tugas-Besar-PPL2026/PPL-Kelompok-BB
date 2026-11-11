{{--
    Seleksi Kandidat (UMKM), daftar pelamar per proyek. PBI-19 / FR-11.
    Data dari CandidateSelectionController@index. Semua angka dihitung dari database.
--}}
@extends('layouts.app')
@section('title', 'Seleksi Kandidat')

@use('App\Support\CandidatePresenter')
@use('App\Support\IdFormat')

@section('content')
    <div class="ph">
        <div>
            <h1>Seleksi Kandidat</h1>
            <p>Tinjau pelamar, masukkan ke daftar terpilih sementara, lalu terima atau tolak sesuai kuota proyek.</p>
        </div>

        @if ($project)
            <div class="act">
                <form method="GET" action="{{ route('umkm.seleksi.index') }}" class="psel">
                    <label for="pilih-proyek">Proyek:</label>
                    <select id="pilih-proyek" name="proyek" onchange="this.form.submit()">
                        @foreach ($projects as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $project->id)>{{ $option->title }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="ic" width="16" height="16"></i>
                    <noscript><button type="submit" class="btn o sm">Tampilkan</button></noscript>
                </form>
            </div>
        @endif
    </div>

    @if (! $project)
        <section class="card">
            <div class="empty">
                <i data-lucide="users" class="ic" width="32" height="32"></i>
                <div class="b mt8">Belum ada proyek yang menerima pelamar</div>
                <div class="sub mt8">Pelamar akan muncul di sini setelah proyek Anda dipublikasikan.</div>
                <a href="{{ route('umkm.proyek.create') }}" class="btn p mt16"><i data-lucide="plus" class="ic" width="16" height="16"></i>Buat Proyek</a>
            </div>
        </section>
    @else
        {{-- Ringkasan statistik --}}
        <div class="grid g4 stats mb16">
            <div class="stat">
                <div class="l">Total pelamar</div>
                <div class="v num">{{ $counts['all'] }}</div>
                <div class="s">{{ $project->published_at ? 'sejak '.IdFormat::date($project->published_at) : 'pada proyek ini' }}</div>
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

        {{-- Kuota kandidat: hanya lamaran berstatus Diterima yang dihitung terisi --}}
        <section class="card quota">
            <div class="between">
                <div class="qt">
                    <i data-lucide="user-check" class="ic" width="20" height="20"></i>
                    <span>Kuota kandidat terisi {{ $quota['accepted'] }} dari {{ $quota['total'] }}</span>
                </div>
                <div class="sub">{{ $quota['full'] ? 'Kuota terpenuhi' : 'Sisa '.$quota['remaining'].' kandidat dapat diterima' }}</div>
            </div>
            <div class="prog" role="progressbar" aria-label="Kuota kandidat terisi" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $quota['percent'] }}">
                <i style="width:{{ $quota['percent'] }}%"></i>
            </div>
        </section>

        {{-- Tab status dan tabel pelamar --}}
        <section class="card tcard">
            <nav class="tabs" aria-label="Filter status pelamar">
                <a href="{{ route('umkm.seleksi.index', ['proyek' => $project->id]) }}" class="{{ $status === null ? 'on' : '' }}" @if ($status === null) aria-current="page" @endif>
                    Semua <span>{{ $counts['all'] }}</span>
                </a>
                @foreach ($tabs as $tab)
                    <a href="{{ route('umkm.seleksi.index', ['proyek' => $project->id, 'status' => $tab->value]) }}" class="{{ $status === $tab ? 'on' : '' }}" @if ($status === $tab) aria-current="page" @endif>
                        {{ $tab->label() }} <span>{{ $counts[$tab->value] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="tscroll">
                <table class="t pl">
                    <thead>
                        <tr>
                            <th>Pelamar</th>
                            <th>Keterampilan</th>
                            <th>Pengalaman</th>
                            <th>Penilaian</th>
                            <th>Dilamar</th>
                            <th>Status</th>
                            <th><span class="sr">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applicants as $application)
                            @php
                                $profile = $application->candidateProfile;
                                $rep = $reputation[$profile->id] ?? ['rating' => null, 'evaluations' => 0, 'completed' => 0];
                                $expCount = $profile->experiences->count();
                                $expMonths = $profile->experiences->sum(fn ($e) => IdFormat::durationToMonths($e->duration) ?? 0);
                                $photo = CandidatePresenter::photoUrl($profile);
                            @endphp
                            <tr>
                                <td>
                                    <div class="pc">
                                        <div class="av">
                                            @if ($photo)
                                                <img src="{{ $photo }}" alt="Foto {{ $profile->full_name }}">
                                            @else
                                                {{ CandidatePresenter::initials($profile->full_name) }}
                                            @endif
                                        </div>
                                        <div>
                                            <div class="nm">{{ $profile->full_name }}</div>
                                            <div class="sm">{{ $profile->university }}</div>
                                            <div class="sm">{{ $profile->city }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="sk">
                                    <div class="tags tight">
                                        @forelse ($profile->skills as $skill)
                                            <span class="tag s">{{ $skill->name }}</span>
                                        @empty
                                            <span class="sub">Belum ada keterampilan</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @if ($expCount > 0)
                                        <div>{{ $expCount }} pengalaman</div>
                                        @if ($expMonths > 0)<div class="sub">{{ IdFormat::months($expMonths) }}</div>@endif
                                    @else
                                        <span class="sub">Belum ada pengalaman</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($rep['rating'] !== null)
                                        <div class="b"><i data-lucide="star" class="ic star" width="14" height="14"></i> {{ IdFormat::rating($rep['rating']) }}</div>
                                        <div class="sub">{{ $rep['completed'] }} proyek selesai</div>
                                    @else
                                        <div class="sub">Belum ada penilaian</div>
                                        @if ($rep['completed'] > 0)<div class="sub">{{ $rep['completed'] }} proyek selesai</div>@endif
                                    @endif
                                </td>
                                <td class="nw">{{ IdFormat::date($application->created_at) }}</td>
                                <td><span class="bd {{ $application->status->badge() }}">{{ $application->status->label() }}</span></td>
                                <td class="ar">
                                    <a href="{{ route('umkm.seleksi.show', ['application' => $application->id, 'status' => $status?->value]) }}" class="btn o sm" aria-label="Lihat detail pelamar {{ $profile->full_name }}">Lihat</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty">
                                        <i data-lucide="inbox" class="ic" width="30" height="30"></i>
                                        <div class="b mt8">
                                            {{ $status ? 'Tidak ada pelamar dengan status '.$status->label() : 'Belum ada pelamar pada proyek ini' }}
                                        </div>
                                        <div class="sub mt8">
                                            {{ $status ? 'Coba pilih tab status lain.' : 'Pelamar akan muncul di sini setelah kandidat mengirim lamaran.' }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($applicants->hasPages())
                <div class="pager">
                    <span>Halaman {{ $applicants->currentPage() }} dari {{ $applicants->lastPage() }} · {{ $applicants->total() }} pelamar</span>
                    <span class="row">
                        @if ($applicants->onFirstPage())
                            <span class="btn o sm dis">Sebelumnya</span>
                        @else
                            <a href="{{ $applicants->previousPageUrl() }}" class="btn o sm">Sebelumnya</a>
                        @endif
                        @if ($applicants->hasMorePages())
                            <a href="{{ $applicants->nextPageUrl() }}" class="btn o sm">Berikutnya</a>
                        @else
                            <span class="btn o sm dis">Berikutnya</span>
                        @endif
                    </span>
                </div>
            @endif
        </section>
    @endif
@endsection
