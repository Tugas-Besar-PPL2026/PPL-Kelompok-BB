{{--
    [PBI-16 / FR-09] Kartu Ajukan Lamaran di kolom kanan halaman Detail Proyek.
    Variabel: $project dan $apply (hasil ApplicationService::formState):
      profile (kelayakan profil), closedReason, existing (lamaran yang sudah ada), canApply, blocker.
    Seluruh pemeriksaan di sini hanya untuk tampilan. Aturan sebenarnya dijalankan ulang di backend saat form dikirim.
--}}
@php
    $profile = $apply['profile'];
    $existing = $apply['existing'];
    $canApply = $apply['canApply'];
    $minChars = \App\Http\Requests\ApplicationRequest::MIN_MESSAGE;
    $profilUrl = Route::has('kandidat.profil.edit') ? route('kandidat.profil.edit') : '#';

    $checks = [
        ['ok' => $profile['eligible'], 'yes' => 'Profil Anda memenuhi syarat melamar', 'no' => 'Profil Anda belum memenuhi syarat melamar'],
        ['ok' => $apply['closedReason'] === null, 'yes' => 'Proyek masih menerima lamaran', 'no' => $apply['closedReason'] ?? ''],
        ['ok' => $existing === null, 'yes' => 'Anda belum melamar proyek ini', 'no' => $existing?->status === \App\Enums\ApplicationStatus::Cancelled ? 'Lamaran Anda pada proyek ini sudah dibatalkan' : 'Anda sudah melamar proyek ini'],
    ];

    // Komponen profil yang dilampirkan otomatis: [ikon, label, keterangan, sudah terisi, wajib]
    $attach = [
        ['user-round', 'Data diri & akademik', $profile['personal'] ? 'Lengkap' : 'Belum lengkap', $profile['personal'], true],
        ['sparkles', 'Keterampilan', $profile['skills'] . ' keterampilan', $profile['skills'] > 0, true],
        ['briefcase', 'Pengalaman kerja', $profile['experiences'] . ' pengalaman kerja', $profile['experiences'] > 0, false],
        ['folder', 'Portofolio', $profile['portfolios'] . ' portofolio', $profile['portfolios'] > 0, false],
    ];
@endphp

<section class="card" id="lamar">
    <div class="ch"><i data-lucide="send" class="ic" width="20" height="20"></i><h3>Ajukan Lamaran</h3></div>

    <ul class="ap-checks" dusk="syarat-lamaran">
        @foreach ($checks as $c)
            <li class="{{ $c['ok'] ? 'ok' : 'no' }}">
                <i data-lucide="{{ $c['ok'] ? 'circle-check' : 'circle-x' }}" class="ic" width="17" height="17"></i>
                <span style="color:var(--ink)">{{ $c['ok'] ? $c['yes'] : $c['no'] }}</span>
            </li>
        @endforeach
    </ul>

    @if ($apply['blocker'] === 'applied')
        <div class="al ok mb16" dusk="status-lamaran">
            <i data-lucide="circle-check" class="ic" width="19" height="19"></i>
            <div>
                <b>{{ $existing->status === \App\Enums\ApplicationStatus::Cancelled ? 'Lamaran Anda sudah dibatalkan' : 'Lamaran Anda sudah terkirim' }}</b>
                Dikirim pada {{ $existing->created_at->locale('id')->translatedFormat('j M Y, H:i') }} ·
                <span class="bd {{ $existing->status->badge() }}">{{ $existing->status->label() }}</span>
                @if ($existing->status === \App\Enums\ApplicationStatus::Cancelled)
                    <div style="margin-top:4px">Lamaran yang sudah dibatalkan tidak dapat diajukan kembali pada proyek ini.</div>
                @endif
            </div>
        </div>
    @elseif ($apply['blocker'] === 'closed')
        <div class="al warn mb16" dusk="lamaran-tertutup">
            <i data-lucide="lock" class="ic" width="19" height="19"></i>
            <div><b>Pengajuan lamaran dinonaktifkan</b>{{ $apply['closedReason'] }}</div>
        </div>
    @elseif ($apply['blocker'] === 'profile')
        <div class="al warn mb16" dusk="profil-belum-lengkap">
            <i data-lucide="triangle-alert" class="ic" width="19" height="19"></i>
            <div>
                <b>Lengkapi profil sebelum melamar</b>
                Silakan {{ implode(' dan ', $profile['missing']) }}.
                <a href="{{ $profilUrl }}" style="font-weight:600">Buka Profil &amp; Portofolio</a>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('kandidat.proyek.lamaran.store', $project) }}" id="form-lamaran">
        @csrf
        <div class="fl">
            <label for="message">Pesan lamaran <i>*</i></label>
            <div class="in ta @error('message') err @enderror">
                <textarea id="message" name="message" rows="6" dusk="pesan-lamaran" @disabled(! $canApply)
                    placeholder="Perkenalkan diri Anda dan jelaskan kemampuan yang sesuai dengan proyek ini.">{{ old('message', $existing?->message) }}</textarea>
            </div>
            @error('message')
                <div class="hint err" dusk="error-pesan">{{ $message }}</div>
            @enderror
            <div class="hint ap-counter">
                <span>Jelaskan ketertarikan dan kesesuaian kemampuan Anda. Minimal {{ $minChars }} karakter.</span>
                <span id="hitung-pesan" class="num" data-min="{{ $minChars }}"></span>
            </div>
        </div>

        <div class="ap-attach" dusk="profil-dilampirkan">
            <div class="ttl">Dilampirkan otomatis dari profil</div>
            @foreach ($attach as [$icon, $label, $note, $filled, $required])
                <div class="it">
                    <i data-lucide="{{ $icon }}" class="ic" width="16" height="16"></i>
                    <span>{{ $label }}</span>
                    <span class="st">
                        <span class="sub">{{ $note }}</span>
                        <span class="bd {{ $required ? 'info' : 'grey' }}">{{ $required ? 'Wajib' : 'Opsional' }}</span>
                        @if ($filled)
                            <i data-lucide="circle-check" class="ic mark ok" width="16" height="16"></i>
                        @elseif ($required)
                            <i data-lucide="circle-alert" class="ic mark no" width="16" height="16"></i>
                        @else
                            <i data-lucide="minus-circle" class="ic mark na" width="16" height="16"></i>
                        @endif
                    </span>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn p" id="btn-kirim" dusk="kirim-lamaran" @disabled(! $canApply)>
            <i data-lucide="send" class="ic" width="16" height="16"></i>{{ $apply['blocker'] === 'applied' ? 'Lamaran Terkirim' : 'Kirim Lamaran' }}
        </button>
        <div class="hint mt8">Satu kandidat hanya dapat mengirim satu lamaran untuk setiap proyek.</div>
    </form>
</section>

@push('scripts')
<script>
(() => {
    const area = document.getElementById('message');
    const counter = document.getElementById('hitung-pesan');
    const form = document.getElementById('form-lamaran');
    const button = document.getElementById('btn-kirim');
    if (!area || !counter) return;

    const min = Number(counter.dataset.min);
    function refresh() {
        const n = area.value.trim().length;
        counter.textContent = n + ' / ' + min + ' karakter';
        counter.style.color = n >= min ? 'var(--ok)' : '';
    }
    area.addEventListener('input', refresh);
    refresh();

    // Cegah klik ganda saat form dikirim. Dipulihkan jika pengguna kembali ke halaman ini.
    form.addEventListener('submit', () => { if (button && !button.disabled) { button.disabled = true; } });
    window.addEventListener('pageshow', e => { if (e.persisted && button && {{ $canApply ? 'true' : 'false' }}) { button.disabled = false; } });
})();
</script>
@endpush
