{{--
    Panel Keputusan Seleksi pada halaman Detail Pelamar.
    Tampilannya mengikuti mockup 4.5.20, yang menggabungkan PBI-19, PBI-20, dan PBI-21.

    PBI-19 (halaman ini) hanya menampilkan status dan catatan seleksi yang tersimpan, jadi panel bersifat baca-saja
    dan tidak mengubah status lamaran. Panel menjadi form aktif secara otomatis setelah PBI-20/PBI-21 mendaftarkan
    route `umkm.seleksi.keputusan` (POST, field: `keputusan` = accept|reject|shortlist, dan `catatan`).

    Variabel: $application, $quota
--}}
@php
    $current = $application->status;
    $canDecide = Route::has('umkm.seleksi.keputusan');

    $options = [
        ['value' => 'accept', 'target' => \App\Enums\ApplicationStatus::Accepted, 'title' => 'Terima pelamar',
            'desc' => $quota['full'] ? 'Kuota sudah terpenuhi.' : 'Sisa kuota '.$quota['remaining'].'. Draf kesepakatan pekerjaan dibuat otomatis.'],
        ['value' => 'reject', 'target' => \App\Enums\ApplicationStatus::Rejected, 'title' => 'Tolak dengan catatan',
            'desc' => 'Catatan wajib diisi dan terlihat oleh kandidat.'],
        ['value' => 'shortlist', 'target' => \App\Enums\ApplicationStatus::Shortlisted, 'title' => 'Masukkan ke terpilih sementara',
            'desc' => 'Simpan ke daftar pendek untuk dibandingkan.'],
    ];
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

    @if ($canDecide)
        <form method="POST" action="{{ route('umkm.seleksi.keputusan', $application) }}">
            @csrf
    @endif

    <div class="opts mt12" role="radiogroup" aria-label="Keputusan seleksi">
        @php $checkedOne = false; @endphp
        @foreach ($options as $opt)
            @php
                $isCurrent = $current === $opt['target'];
                // Mode baca-saja: pilihan yang sama dengan status saat ini yang ditandai. Mode form: pilihan pertama yang boleh dipilih.
                $checked = $canDecide ? (! $isCurrent && ! $checkedOne) : $isCurrent;
                $checkedOne = $checkedOne || $checked;
            @endphp
            <label class="opt">
                <input type="radio" name="keputusan" value="{{ $opt['value'] }}" @checked($checked) @disabled(! $canDecide || $isCurrent)>
                <span class="rd"></span>
                <span>
                    <b>{{ $opt['title'] }}</b>
                    <span class="d">{{ $isCurrent ? 'Status saat ini' : $opt['desc'] }}</span>
                </span>
            </label>
        @endforeach
    </div>

    <div class="fl mt16">
        <label for="catatan-seleksi">Catatan seleksi</label>
        <div class="in ta">
            <textarea id="catatan-seleksi" name="catatan" rows="3" @readonly(! $canDecide)>{{ $canDecide ? old('catatan', $application->selection_note) : $application->selection_note }}</textarea>
        </div>
        <div class="hint">Opsional saat menerima, wajib saat menolak.</div>
    </div>

    <div class="mt16">
        <button type="{{ $canDecide ? 'submit' : 'button' }}" class="btn p" @disabled(! $canDecide)>
            <i data-lucide="check" class="ic" width="16" height="16"></i>Simpan Keputusan
        </button>
    </div>

    @if ($canDecide)
        </form>
    @else
        <div class="al info mt16">
            <i data-lucide="info" class="ic" width="18" height="18"></i>
            <div>Keputusan seleksi belum dapat diubah dari halaman ini. Status dan catatan di atas hanya menampilkan kondisi lamaran saat ini.</div>
        </div>
    @endif
</section>
