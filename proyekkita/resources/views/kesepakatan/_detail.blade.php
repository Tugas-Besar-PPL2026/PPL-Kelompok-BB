{{--
    [PBI-26] Detail kesepakatan yang dipilih, dipakai halaman Kesepakatan & Progres UMKM dan Kandidat.
    Variabel: $detail (AgreementListService::detail), $role ('umkm' | 'kandidat').
    Hanya membaca data. Bagian progres, pembayaran, dan pembatalan pada mockup dikerjakan PBI lain.
--}}
@php
    use App\Enums\AgreementStatus;
    use App\Support\SelectionPresenter;

    $agreement = $detail['agreement'];
    $status = $agreement->status;
    $stageIcon = ['done' => 'circle-check', 'current' => 'circle-dot', 'todo' => 'circle'];
    $withName = $role === 'umkm'
        ? 'bersama '.collect([$detail['candidate']?->full_name, $detail['candidate']?->university])->filter()->join(' · ')
        : collect([$detail['umkm']?->business_name, 'bersama '.$detail['candidate']?->full_name])->filter()->join(' · ');
    $closed = in_array($status, [AgreementStatus::Rejected, AgreementStatus::Cancelled], true);
@endphp

<div id="detail-kesepakatan" dusk="detail-kesepakatan">
    {{-- Kepala detail + linimasa --}}
    <section class="card mt16">
        <div class="ks-head">
            <div>
                <div class="tags">
                    <span class="ks-code" dusk="kode-kesepakatan">{{ $detail['code'] }}</span>
                    <span class="bd {{ $status->badge() }}" dusk="status-kesepakatan">{{ $status->label() }}</span>
                </div>
                <h2 class="ks-title" dusk="judul-proyek">{{ $detail['project']->title }}</h2>
                <div class="sub" dusk="para-pihak">{{ $withName }}</div>
            </div>
            <div class="ks-due" dusk="tenggat-detail">
                <div class="l">Tenggat penyelesaian</div>
                <div class="v">{{ $detail['deadline']['date'] }}</div>
                @unless ($closed || $status === AgreementStatus::Completed)
                    <div class="n {{ $detail['deadline']['late'] ? 'late' : '' }}" dusk="sisa-hari">{{ $detail['deadline']['note'] }}</div>
                @endunless
            </div>
        </div>

        @if ($closed)
            <div class="al bad mt16" dusk="info-ditolak">
                <i data-lucide="circle-x" class="ic" width="18" height="18"></i>
                <div>
                    <b>{{ $status === AgreementStatus::Rejected ? 'Kesepakatan ditolak kandidat' : 'Kesepakatan dibatalkan' }}{{ $agreement->rejected_at ? ' · '.SelectionPresenter::dateTime($agreement->rejected_at) : '' }}</b>
                    @if (filled($agreement->rejection_reason))
                        <span dusk="alasan-penolakan">Alasan: {{ $agreement->rejection_reason }}</span>
                    @endif
                </div>
            </div>
        @else
            <div class="ks-steps" dusk="linimasa-kesepakatan">
                @foreach ($detail['stages'] as $stage)
                    <div class="ks-step {{ $stage['state'] }}">
                        <div class="bar"></div>
                        <div class="lb"><i data-lucide="{{ $stageIcon[$stage['state']] }}" class="ic" width="17" height="17"></i>{{ $stage['label'] }}</div>
                        <div class="nt">{{ $stage['note'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <div class="grid g21 gtop mt16">
        {{-- Ringkasan kesepakatan --}}
        <section class="card" dusk="ringkasan-kesepakatan">
            <div class="ch"><i data-lucide="file-text" class="ic" width="20" height="20"></i><h3>Ringkasan Kesepakatan</h3></div>
            <dl class="kv ks-kv">
                <dt>Ruang lingkup</dt>
                <dd class="ks-text" dusk="ringkasan-ruang-lingkup">{{ $agreement->scope ?: '-' }}</dd>
                <dt>Hasil diserahkan</dt>
                <dd dusk="ringkasan-hasil">
                    @if ($detail['deliverables'] === [])
                        -
                    @else
                        <ul>@foreach ($detail['deliverables'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                    @endif
                </dd>
                <dt>Tenggat</dt>
                <dd dusk="ringkasan-tenggat">{{ $detail['deadline']['date'] }}</dd>
                <dt>Kompensasi</dt>
                <dd class="num" dusk="ringkasan-kompensasi">{{ $detail['terms']['total'] }} · {{ $detail['terms']['count'] }} termin</dd>
                <dt>Batas revisi</dt>
                <dd dusk="ringkasan-revisi">
                    {{ $agreement->revision_limit ?? '-' }} kali{{ $detail['revisionsUsed'] !== null ? ' · terpakai '.$detail['revisionsUsed'] : '' }}
                </dd>
            </dl>

            <div class="ks-h">Tahapan pembayaran</div>
            @include('kesepakatan._termin', ['terms' => $detail['terms']])
        </section>

        {{-- Status kesepakatan --}}
        <section class="card ks-panel" dusk="status-detail">
            <div class="ch"><i data-lucide="list-checks" class="ic" width="20" height="20"></i><h3>Status Kesepakatan</h3></div>
            <div class="tl">
                <div class="it g">
                    <div class="t1">Dikirim ke kandidat</div>
                    <div class="t2" dusk="tanggal-kirim">{{ SelectionPresenter::dateTime($agreement->sent_at) }}</div>
                </div>
                @if ($status === AgreementStatus::Rejected)
                    <div class="it r">
                        <div class="t1">Ditolak kandidat</div>
                        <div class="t2" dusk="tanggal-ditolak">{{ SelectionPresenter::dateTime($agreement->rejected_at) }}</div>
                    </div>
                @elseif ($agreement->approved_at)
                    <div class="it g">
                        <div class="t1">Disetujui kandidat</div>
                        <div class="t2" dusk="tanggal-disetujui">{{ SelectionPresenter::dateTime($agreement->approved_at) }}</div>
                    </div>
                @else
                    <div class="it">
                        <div class="t1">Disetujui kandidat</div>
                        <div class="t2">Menunggu tanggapan kandidat</div>
                    </div>
                @endif
            </div>

            <div class="al info">
                <i data-lucide="info" class="ic" width="18" height="18"></i>
                <div>Isi kesepakatan tidak dapat diubah setelah dikirim kepada kandidat.</div>
            </div>

            @if ($role === 'kandidat' && $status === AgreementStatus::Active && Route::has('kandidat.kesepakatan.penyerahan.index'))
                {{-- Integrasi PBI-31: halaman penyerahan hasil milik anggota lain, hanya ditautkan --}}
                <div class="ks-btns">
                    <a href="{{ route('kandidat.kesepakatan.penyerahan.index', $agreement) }}" class="btn ob" dusk="ke-penyerahan">
                        <i data-lucide="upload" class="ic" width="16" height="16"></i>Penyerahan Hasil
                    </a>
                </div>
            @endif
        </section>
    </div>
</div>
