{{--
    Seleksi Kandidat (UMKM), detail pelamar. PBI-19 / FR-11.
    Kolom kiri: profil, pesan lamaran, keterampilan & pengalaman, portofolio.
    Kolom kanan: panel Keputusan Seleksi (baca-saja sampai PBI-20/21 aktif) dan Riwayat Status Lamaran.
    Data dari CandidateSelectionController@show.
--}}
@extends('layouts.app')
@section('title', 'Detail Pelamar')

@use('App\Enums\ApplicationStatus')
@use('App\Support\SelectionPresenter')

@section('content')
    @php
        $backUrl = route('umkm.seleksi.index', ['proyek' => $project->id, 'status' => $listStatus?->value]);

        // Keterampilan yang dibutuhkan proyek ditampilkan lebih dulu dan lebih tebal.
        $matchedSkills = $profile->skills->filter(fn ($skill) => in_array($skill->id, $requiredSkillIds, true));
        $otherSkills = $profile->skills->reject(fn ($skill) => in_array($skill->id, $requiredSkillIds, true));

        $rating = $reputation['rating'];
        $histories = $application->statusHistories;
    @endphp

    <div class="ph">
        <div>
            <div class="crumb">
                <a href="{{ $backUrl }}">Seleksi Kandidat</a>
                <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
                <a href="{{ $backUrl }}">{{ $project->title }}</a>
                <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
                <span>{{ $profile->full_name }}</span>
            </div>
            <h1>Detail Pelamar</h1>
        </div>
        <div class="act">
            <a href="{{ $backUrl }}" class="btn o"><i data-lucide="arrow-left" class="ic" width="16" height="16"></i>Kembali ke Daftar</a>
        </div>
    </div>

    <div class="grid g21 gtop">
        {{-- ================= Kolom kiri ================= --}}
        <div class="stack">
            {{-- Profil kandidat --}}
            <section class="card">
                <div class="prof">
                    <div class="av lg">
                        {{ SelectionPresenter::initials($profile->full_name) }}
                    </div>
                    <div class="pbody">
                        <div class="row rowwrap">
                            <h2>{{ $profile->full_name }}</h2>
                            <span class="bd {{ $application->status->badge() }}">{{ $application->status->label() }}</span>
                        </div>
                        <div class="hl">{{ $profile->headline }}</div>

                        <div class="meta3">
                            <div><i data-lucide="graduation-cap" class="ic" width="16" height="16"></i><span>{{ $profile->university }}</span></div>
                            <div><i data-lucide="book-open" class="ic" width="16" height="16"></i><span>{{ $profile->major }}</span></div>
                            <div><i data-lucide="map-pin" class="ic" width="16" height="16"></i><span>{{ $profile->city }}</span></div>
                        </div>

                        <div class="rate">
                            @if ($rating !== null)
                                <span class="stars" aria-hidden="true">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i data-lucide="star" class="ic star {{ $i <= round($rating) ? '' : 'off' }}" width="16" height="16"></i>
                                    @endfor
                                </span>
                                <b>{{ SelectionPresenter::decimal($rating) }}</b>
                                <span class="sub">rata-rata dari {{ $reputation['evaluations'] }} evaluasi UMKM · {{ $reputation['completed'] }} proyek selesai</span>
                            @else
                                <span class="sub">Belum ada penilaian · {{ $reputation['completed'] }} proyek selesai</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="hr"></div>
                <h4 class="sech">Ringkasan diri</h4>
                @if (filled(trim((string) $profile->summary)))
                    <p class="msg">{{ $profile->summary }}</p>
                @else
                    <p class="sub">Kandidat belum mengisi ringkasan diri.</p>
                @endif
            </section>

            {{-- Pesan lamaran --}}
            <section class="card">
                <div class="ch"><i data-lucide="message-square-text" class="ic" width="20" height="20"></i><h3>Pesan Lamaran</h3></div>
                @if (filled(trim((string) $application->message)))
                    <p class="msg">{{ $application->message }}</p>
                @else
                    <p class="sub">Kandidat tidak menuliskan pesan lamaran.</p>
                @endif
                <div class="sub mt8">Dikirim {{ SelectionPresenter::dateTime($application->created_at) }}</div>
            </section>

            {{-- Keterampilan & pengalaman --}}
            <section class="card">
                <div class="ch"><i data-lucide="sparkles" class="ic" width="20" height="20"></i><h3>Keterampilan &amp; Pengalaman</h3></div>

                @if ($profile->skills->isEmpty())
                    <p class="sub">Kandidat belum menambahkan keterampilan.</p>
                @else
                    <div class="tags">
                        @foreach ($matchedSkills as $skill)
                            <span class="tag req">{{ $skill->name }}</span>
                        @endforeach
                        @foreach ($otherSkills as $skill)
                            <span class="tag oth">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                    @if ($matchedSkills->isNotEmpty())
                        <div class="sub mt12">Keterampilan yang dibutuhkan proyek ditandai lebih tebal.</div>
                    @endif
                @endif

                <div class="hr"></div>

                @if ($profile->experiences->isEmpty())
                    <p class="sub">Kandidat belum menambahkan pengalaman kerja.</p>
                @else
                    <div class="explist">
                    @foreach ($profile->experiences as $experience)
                    <div class="exp">
                        <div class="ico"><i data-lucide="briefcase" class="ic" width="18" height="18"></i></div>
                        <div>
                            <div class="b">{{ $experience->work_type }}</div>
                            <div class="sub">{{ $experience->duration }}</div>
                            @if (filled($experience->description))
                                <div class="expd">{{ $experience->description }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    </div>
                @endif
            </section>

            {{-- Portofolio --}}
            <section class="card">
                <div class="ch"><i data-lucide="folder-open" class="ic" width="20" height="20"></i><h3>Portofolio</h3></div>

                @if ($profile->portfolios->isEmpty())
                    <p class="sub">Kandidat belum menambahkan portofolio.</p>
                @else
                    <div class="pfg">
                        @foreach ($profile->portfolios as $portfolio)
                            @php
                                $url = SelectionPresenter::safeUrl($portfolio->link);
                                $hasFile = filled($portfolio->file_path);
                                $fileInfo = $hasFile ? ($files[$portfolio->id] ?? null) : null;
                            @endphp
                            <div class="pf">
                                <div class="ico"><i data-lucide="{{ $url || ! $hasFile ? 'link' : 'file-text' }}" class="ic" width="18" height="18"></i></div>
                                <div class="pfb">
                                    <div class="nm">{{ $portfolio->title }}</div>
                                    @if (filled($portfolio->description))
                                        <div class="sub">{{ $portfolio->description }}</div>
                                    @endif
                                    @if ($url)
                                        <a class="lk" href="{{ $url }}" target="_blank" rel="noopener noreferrer nofollow">
                                            <i data-lucide="external-link" class="ic" width="13" height="13"></i>
                                            <span>{{ SelectionPresenter::displayUrl($url) }}</span>
                                        </a>
                                    @endif
                                    @if ($hasFile)
                                        @if ($fileInfo)
                                            <a class="lk" href="{{ route('umkm.seleksi.portofolio', ['application' => $application->id, 'portfolio' => $portfolio->id]) }}" target="_blank" rel="noopener noreferrer">
                                                <i data-lucide="file-text" class="ic" width="13" height="13"></i>
                                                <span>{{ basename($portfolio->file_path) }} · {{ SelectionPresenter::fileSize($fileInfo['size']) }}</span>
                                            </a>
                                        @else
                                            <div class="sub">{{ basename($portfolio->file_path) }} (berkas tidak ditemukan)</div>
                                        @endif
                                    @endif
                                    @if (! $url && ! $hasFile)
                                        <div class="sub">Tidak ada tautan atau dokumen.</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- ================= Kolom kanan ================= --}}
        <div class="stack">
            @include('umkm.seleksi._keputusan')

            <section class="card">
                <div class="ch"><i data-lucide="history" class="ic" width="20" height="20"></i><h3>Riwayat Status Lamaran</h3></div>

                @if ($histories->isEmpty())
                    <p class="sub">Belum ada riwayat perubahan status untuk lamaran ini.</p>
                @else
                    <div class="tl">
                        @foreach ($histories as $history)
                            @php
                                // Riwayat diurutkan dari yang terbaru. Catatan pertama (paling lama) berstatus Menunggu Keputusan adalah lamaran masuk.
                                $isInitial = $loop->last && $history->status === ApplicationStatus::Pending;
                                $dot = $isInitial ? 'g' : match ($history->status) {
                                    ApplicationStatus::Accepted => 'g',
                                    ApplicationStatus::Rejected => 'r',
                                    ApplicationStatus::Pending => 'w',
                                    default => '',
                                };
                            @endphp
                            <div class="it {{ $dot }}">
                                <div class="t1">{{ $isInitial ? 'Lamaran diterima sistem' : \Illuminate\Support\Str::ucfirst(mb_strtolower($history->status->label())) }}</div>
                                <div class="t2">{{ SelectionPresenter::dateTime($history->changed_at) }}</div>
                                @if ($isInitial)
                                    <div class="t3">Status awal: {{ $history->status->label() }}</div>
                                @endif
                                @if (filled($history->note))
                                    <div class="t3">Catatan: {{ $history->note }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
