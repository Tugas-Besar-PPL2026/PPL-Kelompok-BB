{{--
    [PBI-13] Proyek Saya (UMKM): ringkasan metrik, tab status, kartu proyek, dan catatan moderasi.
    Variabel: $projects (paginator), $monitoring (ProjectMonitoringService), $status (?ProjectStatus),
              $counts (array jumlah per status + 'all'), $totalApplicants (int), $tabs (daftar ProjectStatus).
--}}
@extends('layouts.app')
@section('title', 'Proyek Saya')

@push('styles')
<style>
    .pm-top{display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap}
    .pm-title{font-size:18px;font-weight:600;letter-spacing:-.2px;margin:0}
    .pm-meta{display:flex;gap:22px;flex-wrap:wrap;margin-top:14px;padding-top:14px;border-top:1px solid var(--line2)}
    .pm-meta .l{font-size:12px;color:var(--mut)}
    .pm-meta .v{font-weight:600}
    .pm-foot{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px}
    .pm-foot .btn{margin-left:auto}
    @media (max-width:640px){.pm-foot .btn{margin-left:0;width:100%;justify-content:center}}
</style>
@endpush

@section('content')
    @php
        $rp = fn ($n) => 'Rp' . number_format((float) $n, 0, ',', '.');
        $tgl = fn ($d) => $d?->locale('id')->translatedFormat('j M Y');
    @endphp

    <div class="ph">
        <div>
            <h1>Proyek Saya</h1>
            <p>Pantau status proyek, hasil moderasi admin, dan jumlah pelamar di satu tempat.</p>
        </div>
        <div class="act">
            <a href="{{ route('umkm.proyek.create') }}" class="btn p" dusk="buat-proyek">
                <i data-lucide="plus" class="ic" width="16" height="16"></i>Buat Proyek
            </a>
        </div>
    </div>

    {{-- Ringkasan metrik --}}
    <div class="grid g4 stats mb16">
        <div class="stat" dusk="metrik-dipublikasikan">
            <div class="l">Dipublikasikan</div>
            <div class="v num" dusk="nilai-dipublikasikan">{{ $counts['published'] }}</div>
            <div class="s">terbuka untuk kandidat</div>
        </div>
        <div class="stat" dusk="metrik-menunggu-moderasi">
            <div class="l">Menunggu Moderasi</div>
            <div class="v num" dusk="nilai-menunggu-moderasi">{{ $counts['pending_review'] }}</div>
            <div class="s">sedang diperiksa admin</div>
        </div>
        <div class="stat" dusk="metrik-perlu-perbaikan">
            <div class="l">Perlu Perbaikan</div>
            <div class="v num" dusk="nilai-perlu-perbaikan">{{ $counts['revision_requested'] }}</div>
            <div class="s">menunggu revisi Anda</div>
        </div>
        <div class="stat" dusk="metrik-total-pelamar">
            <div class="l">Total Pelamar</div>
            <div class="v num" dusk="nilai-total-pelamar">{{ $totalApplicants }}</div>
            <div class="s">di semua proyek</div>
        </div>
    </div>

    @if ($counts['all'] === 0)
        <section class="card" dusk="proyek-kosong">
            <div class="empty">
                <i data-lucide="briefcase" class="ic" width="32" height="32"></i>
                <div class="b mt8">Belum ada proyek</div>
                <div class="sub mt8">Buat draf proyek pertama Anda, lalu ajukan ke admin untuk dimoderasi.</div>
                <a href="{{ route('umkm.proyek.create') }}" class="btn p mt16">
                    <i data-lucide="plus" class="ic" width="16" height="16"></i>Buat Proyek
                </a>
            </div>
        </section>
    @else
        {{-- Tab status --}}
        <section class="card tcard">
            <nav class="tabs" dusk="tab-status-proyek" aria-label="Filter status proyek">
                <a href="{{ route('umkm.proyek.index') }}" dusk="tab-semua" class="{{ $status === null ? 'on' : '' }}" @if ($status === null) aria-current="page" @endif>
                    Semua <span>{{ $counts['all'] }}</span>
                </a>
                @foreach ($tabs as $tab)
                    <a href="{{ route('umkm.proyek.index', ['status' => $tab->value]) }}" dusk="tab-{{ $tab->value }}" class="{{ $status === $tab ? 'on' : '' }}" @if ($status === $tab) aria-current="page" @endif>
                        {{ $tab->label() }} <span>{{ $counts[$tab->value] }}</span>
                    </a>
                @endforeach
            </nav>
        </section>

        @forelse ($projects as $project)
            @php
                $moderation = $monitoring->latestModeration($project);
                $action = $monitoring->actionFor($project);
            @endphp
            <article class="card pm-card" dusk="kartu-proyek-{{ $project->id }}">
                <div class="pm-top">
                    <div>
                        <h3 class="pm-title">{{ $project->title }}</h3>
                        <div class="sub mt8">{{ $project->category?->name }}</div>
                    </div>
                    <span class="bd {{ $project->status->badge() }}" style="margin-left:auto" dusk="status-proyek-{{ $project->id }}">{{ $project->status->label() }}</span>
                </div>

                {{-- Hasil moderasi terakhir (hanya jika proyek pernah diperiksa admin) --}}
                @if ($moderation)
                    <div class="al {{ $moderation->decision->badge() }} mt16" dusk="catatan-moderasi-{{ $project->id }}">
                        <i data-lucide="message-square-text" class="ic" width="19" height="19"></i>
                        <div>
                            <b>Hasil moderasi terakhir: {{ $moderation->decision->label() }}</b>
                            {{ $moderation->note ?: 'Admin tidak menyertakan catatan.' }}
                            <div class="sub mt8">{{ $tgl($moderation->created_at) }}</div>
                        </div>
                    </div>
                @endif

                <div class="pm-meta">
                    <div><div class="l">Total kompensasi</div><div class="v num">{{ $rp($project->total_compensation) }}</div></div>
                    <div><div class="l">Kuota kandidat</div><div class="v">{{ $project->quota }} orang</div></div>
                    <div><div class="l">Tenggat lamaran</div><div class="v">{{ $tgl($project->application_deadline) }}</div></div>
                    <div><div class="l">Pelamar</div><div class="v" dusk="jumlah-pelamar-{{ $project->id }}">{{ $project->applicants_count }}</div></div>
                </div>

                <div class="pm-foot">
                    @if ($project->status === \App\Enums\ProjectStatus::PendingReview)
                        <span class="sub">Admin sedang memeriksa proyek ini.</span>
                    @elseif ($project->status === \App\Enums\ProjectStatus::Rejected)
                        <span class="sub">Proyek ditolak dan tidak ditampilkan kepada kandidat.</span>
                    @endif

                    @if ($action)
                        <a href="{{ $action['url'] }}" class="{{ $action['style'] }}" dusk="{{ $action['dusk'] }}">{{ $action['label'] }}</a>
                    @endif
                </div>
            </article>
        @empty
            <section class="card" dusk="tab-kosong">
                <div class="empty">
                    <i data-lucide="inbox" class="ic" width="30" height="30"></i>
                    <div class="b mt8">Tidak ada proyek dengan status {{ $status?->label() }}</div>
                    <div class="sub mt8">Coba pilih tab status lain.</div>
                </div>
            </section>
        @endforelse

        @if ($projects->hasPages())
            <div class="pager">
                <span>Halaman {{ $projects->currentPage() }} dari {{ $projects->lastPage() }} · {{ $projects->total() }} proyek</span>
                <span class="row">
                    @if ($projects->onFirstPage())
                        <span class="btn o sm dis">Sebelumnya</span>
                    @else
                        <a href="{{ $projects->previousPageUrl() }}" class="btn o sm">Sebelumnya</a>
                    @endif
                    @if ($projects->hasMorePages())
                        <a href="{{ $projects->nextPageUrl() }}" class="btn o sm">Berikutnya</a>
                    @else
                        <span class="btn o sm dis">Berikutnya</span>
                    @endif
                </span>
            </div>
        @endif
    @endif
@endsection
