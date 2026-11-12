{{--
    Panel Keputusan Seleksi pada halaman Detail Pelamar (mockup 4.5.20).
    PBI-20: Masukkan ke Terpilih Sementara dan Tolak dengan Catatan. PBI-21: Terima Pelamar (dengan kuota).
    Form mengirim `keputusan` (accept|reject|shortlist) dan `catatan` ke route `umkm.seleksi.keputusan`.
    Pilihan yang dinonaktifkan di sini hanya kemudahan tampilan; aturan sebenarnya diperiksa ulang di backend.

    Variabel: $application, $quota, $listStatus
--}}
@use('App\Services\CandidateDecisionService')
@php
    $current = $application->status;
    $allowed = CandidateDecisionService::allowedTargets($current);
    $isFinal = $allowed === [];

    $options = [
        ['value' => 'shortlist', 'target' => \App\Enums\ApplicationStatus::Shortlisted, 'title' => 'Masukkan ke terpilih sementara',
            'desc' => 'Simpan ke daftar pendek untuk dibandingkan. Catatan opsional.'],
        ['value' => 'accept', 'target' => \App\Enums\ApplicationStatus::Accepted, 'title' => 'Terima pelamar',
            'desc' => $quota['full']
                ? 'Kuota sudah terpenuhi, pelamar tidak dapat diterima lagi.'
                : 'Sisa kuota '.$quota['remaining'].'. Catatan opsional.'],
        ['value' => 'reject', 'target' => \App\Enums\ApplicationStatus::Rejected, 'title' => 'Tolak dengan catatan',
            'desc' => 'Catatan wajib diisi dan terlihat oleh kandidat.'],
    ];

    // Pilihan yang boleh dipilih: transisi status diizinkan, dan untuk "terima" kuota belum penuh.
    $selectable = fn (array $opt) => in_array($opt['target'], $allowed, true)
        && ! ($opt['value'] === 'accept' && $quota['full']);

    $oldChoice = old('keputusan');
    $firstSelectable = collect($options)->first($selectable)['value'] ?? null;
    $preselect = $oldChoice && collect($options)->contains(fn ($o) => $o['value'] === $oldChoice) ? $oldChoice : $firstSelectable;
@endphp

<section class="card">
    <div class="ch"><i data-lucide="gavel" class="ic" width="20" height="20"></i><h3>Keputusan Seleksi</h3></div>

    <div class="qbox">
        <div class="between">
            <b>Kuota kandidat</b>
            <span class="small">{{ $quota['accepted'] }} dari {{ $quota['total'] }} terisi</span>
        </div>
        <div class="prog mt8" role="progressbar" aria-label="Kuota kandidat terisi" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $quota['percent'] }}">
            <i style="width:{{ $quota['percent'] }}%"></i>
        </div>
    </div>

    @if ($errors->any())
        <div class="al bad mt12" role="alert">
            <i data-lucide="circle-alert" class="ic" width="18" height="18"></i>
            <div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
        </div>
    @endif

    @if (filled($application->selection_note))
        <div class="al info mt12">
            <i data-lucide="message-square" class="ic" width="18" height="18"></i>
            <div><b>Catatan seleksi tersimpan:</b> {{ $application->selection_note }}</div>
        </div>
    @endif

    @if ($isFinal)
        <div class="al info mt12">
            <i data-lucide="lock" class="ic" width="18" height="18"></i>
            <div>
                Status lamaran ini <b>{{ $current->label() }}</b> dan tidak dapat diubah lagi lewat halaman ini.
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('umkm.seleksi.keputusan', ['application' => $application->id, 'status' => $listStatus?->value]) }}">
            @csrf

            <div class="opts mt12" role="radiogroup" aria-label="Keputusan seleksi">
                @foreach ($options as $opt)
                    @php $ok = $selectable($opt); @endphp
                    <label class="opt">
                        <input type="radio" name="keputusan" value="{{ $opt['value'] }}" @checked($ok && $preselect === $opt['value']) @disabled(! $ok)>
                        <span class="rd"></span>
                        <span>
                            <b>{{ $opt['title'] }}</b>
                            <span class="d">{{ $current === $opt['target'] ? 'Status saat ini' : $opt['desc'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="fl mt16">
                <label for="catatan-seleksi">Catatan seleksi</label>
                <div class="in ta">
                    <textarea id="catatan-seleksi" name="catatan" rows="3" maxlength="1000">{{ old('catatan') }}</textarea>
                </div>
                <div class="hint">Opsional saat menerima atau memasukkan ke terpilih sementara, wajib saat menolak.</div>
            </div>

            <div class="mt16">
                <button type="submit" class="btn p" @disabled($firstSelectable === null)>
                    <i data-lucide="check" class="ic" width="16" height="16"></i>Simpan Keputusan
                </button>
            </div>
        </form>
    @endif
</section>
