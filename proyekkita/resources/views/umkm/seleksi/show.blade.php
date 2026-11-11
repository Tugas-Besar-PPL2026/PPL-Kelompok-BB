{{--
    Detail Pelamar (PBI-19, mockup 4.5.20): kolom kiri informasi kandidat, kolom kanan keputusan seleksi dan riwayat status.
    Data dari CandidateSelectionController@show: $application, $project, $profile, $stats, $quota, $skills,
    $portfolios, $histories, $backStatus.
--}}
@extends('layouts.app')
@section('title', 'Detail Pelamar')

{{-- Gaya khusus halaman ini (hanya berlaku di dalam <main>); tidak mengubah navbar/sidebar. --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/seleksi.css') }}">
@endpush


@use('App\Support\SelectionPresenter', 'P')
@use('App\Enums\ApplicationStatus')

@section('content')
    @php
        $backUrl = route('umkm.seleksi.index', array_filter(['proyek' => $project->id, 'status' => $backStatus?->value]));
        $hasRequiredSkills = $project->skills->isNotEmpty();
    @endphp

    <div class="ph">
        <div>
            <nav class="crumb" aria-label="Breadcrumb">
                <a href="{{ route('umkm.seleksi.index') }}">Seleksi Kandidat</a>
                <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
                <a href="{{ route('umkm.seleksi.index', ['proyek' => $project->id]) }}">{{ $project->title }}</a>
                <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
                <span>{{ $profile->full_name }}</span>
            </nav>
            <h1>Detail Pelamar</h1>
        </div>
        <div class="act">
            <a href="{{ $backUrl }}" class="btn o"><i data-lucide="arrow-left" class="ic" width="16" height="16"></i>Kembali ke Daftar</a>
        </div>
    </div>

    <div class="grid g21">
        {{-- ================= Kolom kiri: informasi kandidat ================= --}}
        <div>
            {{-- Profil kandidat --}}
            <section class="card">
                <div class="pcard">
                    @include('umkm.seleksi._avatar', ['profile' => $profile, 'size' => 'xl'])
                    <div class="info">
                        <div class="pname">
                            <h2>{{ $profile->full_name }}</h2>
                            <span class="bd {{ $application->status->badge() }}">{{ $application->status->label() }}</span>
                        </div>
                        <div class="headline">{{ $profile->headline }}</div>

                        <div class="pmeta">
                            <div><i data-lucide="school" class="ic" width="16" height="16"></i><span>{{ $profile->university }}</span></div>
                            <div><i data-lucide="book-open" class="ic" width="16" height="16"></i><span>{{ $profile->major }}</span></div>
                            <div><i data-lucide="map-pin" class="ic" width="16" height="16"></i><span>{{ $profile->city }}</span></div>
                        </div>

                        <div class="rate">
                            @if ($stats['rating'] !== null)
                                <span class="stars" aria-hidden="true">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i data-lucide="star" class="ic star {{ $i <= round($stats['rating']) ? '' : 'off' }}" width="17" height="17"></i>
                                    @endfor
                                </span>
                                <b>{{ P::decimal($stats['rating']) }}</b>
                                <span>rata-rata dari {{ $stats['evaluations'] }} evaluasi UMKM · {{ $stats['completed'] }} proyek selesai</span>
                            @else
                                <span>Belum ada penilaian · {{ $stats['completed'] }} proyek selesai</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="sechead">Ringkasan diri</div>
                @if (filled(trim((string) $profile->summary)))
                    <p class="msg">{{ $profile->summary }}</p>
                @else
                    <p class="muted-empty">Kandidat belum mengisi ringkasan diri.</p>
                @endif
            </section>

            {{-- Pesan lamaran --}}
            <section class="card">
                <div class="ch"><i data-lucide="message-square-text" class="ic" width="20" height="20"></i><h3>Pesan Lamaran</h3></div>
                @if (filled(trim((string) $application->message)))
                    <p class="msg">{{ $application->message }}</p>
                @else
                    <p class="muted-empty">Kandidat tidak menyertakan pesan lamaran.</p>
                @endif
                <div class="when">Dikirim {{ P::dateTime($application->created_at) }}</div>
            </section>

            {{-- Keterampilan & pengalaman --}}
            <section class="card">
                <div class="ch"><i data-lucide="sparkles" class="ic" width="20" height="20"></i><h3>Keterampilan &amp; Pengalaman</h3></div>

                @if ($skills->isEmpty())
                    <p class="muted-empty">Kandidat belum menambahkan keterampilan.</p>
                @else
                    <div class="tags">
                        @foreach ($skills as $skill)
                            <span class="tag {{ $skill['required'] ? 'req' : '' }}">{{ $skill['name'] }}</span>
                        @endforeach
                    </div>
                    @if ($hasRequiredSkills)
                        <div class="sub mt12">Keterampilan yang dibutuhkan proyek ditandai lebih tebal.</div>
                    @endif
                @endif

                <div class="hr"></div>

                @if ($profile->experiences->isEmpty())
                    <p class="muted-empty">Kandidat belum menambahkan pengalaman kerja.</p>
                @else
                    <div class="exps">
                        @foreach ($profile->experiences as $experience)
                            <div class="exp">
                                <div class="ico"><i data-lucide="briefcase-business" class="ic" width="18" height="18"></i></div>
                                <div>
                                    <div class="tt">{{ $experience->work_type }}</div>
                                    <div class="du">{{ $experience->duration }}</div>
                                    @if (filled($experience->description))
                                        <div class="ds">{{ $experience->description }}</div>
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

                @if ($portfolios->isEmpty())
                    <p class="muted-empty">Kandidat belum menambahkan portofolio.</p>
                @else
                    <div class="pf-grid">
                        @foreach ($portfolios as $item)
                            @php
                                $primary = $item['url']
                                    ?? ($item['file_available'] ? route('umkm.seleksi.portofolio', [$application, $item['id']]) : null);
                            @endphp
                            <div class="pf">
                                <div class="ico"><i data-lucide="{{ $item['url'] ? 'link-2' : 'file-text' }}" class="ic" width="19" height="19"></i></div>
                                <div class="body">
                                    <div class="tt">{{ $item['title'] }}</div>
                                    @if (filled($item['description']))
                                        <div class="ds">{{ $item['description'] }}</div>
                                    @endif
                                    @if ($item['url'])
                                        <div class="lk"><i data-lucide="link-2" class="ic" width="13" height="13"></i><a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer nofollow">{{ $item['url_label'] }}</a></div>
                                    @endif
                                    @if ($item['has_file'])
                                        <div class="lk">
                                            <i data-lucide="file-text" class="ic" width="13" height="13"></i>
                                            @if ($item['file_available'])
                                                <a href="{{ route('umkm.seleksi.portofolio', [$application, $item['id']]) }}" target="_blank" rel="noopener">{{ $item['file_name'] }}</a>
                                                <span>· {{ $item['file_size'] }}</span>
                                            @else
                                                <span>{{ $item['file_name'] }} · berkas tidak tersedia</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if (! $item['url'] && ! $item['has_file'])
                                        <div class="lk">Tidak ada tautan atau dokumen.</div>
                                    @endif
                                </div>
                                @if ($primary)
                                    <a href="{{ $primary }}" target="_blank" rel="noopener noreferrer nofollow" class="open" aria-label="Buka {{ $item['title'] }}"><i data-lucide="external-link" class="ic" width="17" height="17"></i></a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- ================= Kolom kanan: keputusan dan riwayat ================= --}}
        <div>
            @include('umkm.seleksi._panel-keputusan', ['application' => $application, 'quota' => $quota])

            <section class="card">
                <div class="ch"><i data-lucide="history" class="ic" width="20" height="20"></i><h3>Riwayat Status Lamaran</h3></div>

                @if ($histories->isEmpty())
                    <p class="muted-empty">Belum ada riwayat status untuk lamaran ini.</p>
                @else
                    <div class="tl">
                        @foreach ($histories as $history)
                            @php
                                // Baris terakhir adalah yang paling awal. Bila itu status awal tanpa catatan, tampil sebagai penerimaan oleh sistem.
                                $isInitial = $loop->last && $history->status === ApplicationStatus::Pending && blank($history->note);
                                $dot = match (true) {
                                    $isInitial, $history->status === ApplicationStatus::Accepted => 'g',
                                    $history->status === ApplicationStatus::Rejected => 'r',
                                    $history->status === ApplicationStatus::Cancelled => 'w',
                                    default => '',
                                };
                            @endphp
                            <div class="it {{ $dot }}">
                                <div class="t1">{{ $isInitial ? 'Lamaran diterima sistem' : $history->status->label() }}</div>
                                <div class="t2">{{ P::dateTime($history->changed_at) }}</div>
                                @if ($isInitial)
                                    <div class="t3">Status awal: {{ $history->status->label() }}</div>
                                @elseif (filled($history->note))
                                    <div class="t3 note">Catatan: {{ $history->note }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
