{{--
    [PBI-32 / FR-15] Panel Keputusan Peninjauan.
    Variabel: $agreement, $latestModel, $revision (used, limit, remaining, percent, canRequest), $decision (canDecide, message).
    Batas revisi (BR-32-06): opsi "Minta revisi" nonaktif bila revisi terpakai sudah mencapai revision_limit.
--}}
@php
    $rules = \App\Http\Requests\SubmissionReviewRequest::class;
    $oldDecision = old('decision');
@endphp

<section class="card" dusk="panel-keputusan-tinjau">
    <div class="ch">
        <i data-lucide="gavel" class="ic" width="18" height="18"></i>
        <h3>Keputusan Peninjauan</h3>
    </div>

    <div class="tr-quota" dusk="kuota-revisi-umkm">
        <div class="row"><span>Revisi terpakai</span><span>{{ $revision['used'] }} dari {{ $revision['limit'] }}</span></div>
        <div class="bar"><span style="width:{{ $revision['percent'] }}%"></span></div>
    </div>

    @if (! $decision['canDecide'])
        <div class="al info" dusk="info-keputusan">
            <i data-lucide="info" class="ic" width="19" height="19"></i>
            <div>{{ $decision['message'] }}</div>
        </div>
    @else
        @if ($errors->any())
            <div class="al bad mb16" dusk="galat-keputusan">
                <i data-lucide="circle-alert" class="ic" width="19" height="19"></i>
                <div>Keputusan belum dapat disimpan. Periksa kembali isian yang ditandai.</div>
            </div>
        @endif

        <form method="POST" action="{{ route('umkm.kesepakatan.tinjau.store', $agreement) }}" id="form-keputusan" dusk="form-keputusan" novalidate>
            @csrf
            <input type="hidden" name="submission_id" value="{{ $latestModel->id }}">

            <label class="tr-opt" dusk="opsi-setujui">
                <input type="radio" name="decision" value="approve" dusk="keputusan-setujui" @checked($oldDecision === 'approve')>
                <div><b>Setujui hasil pekerjaan</b><span class="sub">Status hasil menjadi Disetujui.</span></div>
            </label>

            <label class="tr-opt {{ $revision['canRequest'] ? '' : 'off' }}" dusk="opsi-revisi">
                <input type="radio" name="decision" value="revision" dusk="keputusan-revisi"
                       @checked($oldDecision === 'revision') @disabled(! $revision['canRequest'])>
                <div>
                    <b>Minta revisi</b>
                    <span class="sub" dusk="keterangan-revisi">
                        @if ($revision['canRequest'])
                            Sisa {{ $revision['remaining'] }} kali revisi · catatan perbaikan wajib diisi.
                        @else
                            Batas revisi sudah habis. Hasil pekerjaan hanya dapat disetujui.
                        @endif
                    </span>
                </div>
            </label>
            @error('decision')
                <div class="hint err" dusk="error-keputusan">{{ $message }}</div>
            @enderror

            <div class="fl" style="margin-top:12px">
                <label for="note">Catatan</label>
                <div class="in @error('note') err @enderror">
                    <textarea id="note" name="note" rows="4" dusk="catatan-keputusan"
                              placeholder="Opsional saat menyetujui; wajib saat meminta revisi.">{{ old('note') }}</textarea>
                </div>
                <div class="ps-counter">
                    @error('note')
                        <div class="hint err" dusk="error-catatan">{{ $message }}</div>
                    @else
                        <div class="hint">Catatan revisi dikirim ke kandidat sebagai acuan perbaikan.</div>
                    @enderror
                    <div class="hint" dusk="hitung-catatan"><span id="tr-note-count">0</span>/{{ $rules::MAX_NOTE }}</div>
                </div>
            </div>

            <div style="margin-top:14px">
                <button type="submit" class="btn p" id="btn-simpan-keputusan" dusk="simpan-keputusan-tinjau">
                    <i data-lucide="check" class="ic" width="16" height="16"></i><span id="btn-keputusan-label">Simpan Keputusan</span>
                </button>
            </div>
        </form>

        <div class="al info" style="margin-top:14px">
            <i data-lucide="info" class="ic" width="19" height="19"></i>
            <div>Setelah disetujui, lanjutkan ke pencatatan pembayaran termin sesuai kesepakatan.</div>
        </div>

        @push('scripts')
        <script>
        (() => {
            const form = document.getElementById('form-keputusan');
            if (!form) return;
            const note = document.getElementById('note');
            const count = document.getElementById('tr-note-count');
            const render = () => { count.textContent = Array.from(note.value.trim()).length; };
            note.addEventListener('input', render);
            render();
            form.addEventListener('submit', () => {
                const button = document.getElementById('btn-simpan-keputusan');
                button.disabled = true;
                document.getElementById('btn-keputusan-label').textContent = 'Menyimpan…';
            });
        })();
        </script>
        @endpush
    @endif
</section>
