{{--
    [PBI-31] Form Kirim Hasil Pekerjaan (Versi 1) dan [PBI-33] Kirim Versi Perbaikan (Versi n+1, setelah revisi diminta),
    atau kartu info bila penyerahan belum/tidak dapat dikirim.
    Variabel: $agreement, $form (SubmissionService::formState).
    Validasi utama tetap di server (SubmissionRequest); pemeriksaan di browser hanya umpan balik cepat.
--}}
@php
    $rules = \App\Http\Requests\SubmissionRequest::class;
    $maxDesc = $rules::MAX_DESCRIPTION;
    $isRevision = ($form['mode'] ?? 'first') === 'revision';
    $infoClass = match ($form['blocker']) {
        'agreement' => 'warn',
        'approved' => 'ok',
        default => 'info',
    };
@endphp

<section class="card" dusk="kartu-penyerahan">
    @if ($form['canSubmit'])
        <div class="ch">
            <i data-lucide="upload" class="ic" width="18" height="18"></i>
            <h3 dusk="judul-form-penyerahan">{{ $isRevision ? 'Kirim Versi Perbaikan' : 'Kirim Hasil Pekerjaan' }} · Versi {{ $form['nextVersion'] }}</h3>
        </div>

        @if ($errors->any())
            <div class="al bad mb16" dusk="galat-validasi">
                <i data-lucide="circle-alert" class="ic" width="19" height="19"></i>
                <div>Penyerahan belum dapat dikirim. Periksa kembali isian yang ditandai.</div>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('kandidat.kesepakatan.penyerahan.store', $agreement) }}"
            enctype="multipart/form-data"
            id="form-penyerahan"
            dusk="form-penyerahan"
            novalidate
        >
            @csrf

            <div class="fl mb16">
                <label for="berkas_hasil">Berkas hasil pekerjaan</label>
                <label for="berkas_hasil" class="ps-drop @error('file') err @enderror" id="ps-drop" dusk="area-unggah">
                    <i data-lucide="cloud-upload" class="ic" width="26" height="26" style="color:var(--pri)"></i>
                    <div class="t" id="ps-drop-title">Seret file ke sini atau pilih file</div>
                    <div class="s">PDF, JPG, PNG, atau ZIP · maksimal 25 MB</div>
                    <input
                        type="file"
                        id="berkas_hasil"
                        name="file"
                        dusk="input-berkas"
                        accept=".pdf,.jpg,.jpeg,.png,.zip,application/pdf,image/jpeg,image/png,application/zip"
                        style="display:none"
                    >
                </label>

                <div class="ps-file" id="ps-file" dusk="berkas-terpilih" hidden>
                    <div class="fi"><i data-lucide="file-text" class="ic" width="18" height="18"></i></div>
                    <div style="min-width:0">
                        <div class="nm" id="ps-file-name" dusk="nama-berkas-terpilih"></div>
                        <div class="sub" id="ps-file-meta" dusk="status-berkas-terpilih"></div>
                    </div>
                    <span class="mark" id="ps-file-mark"></span>
                    <button type="button" id="ps-file-remove" dusk="batal-berkas" aria-label="Batalkan pilihan berkas">
                        <i data-lucide="x" class="ic" width="17" height="17"></i>
                    </button>
                </div>

                @error('file')
                    <div class="hint err" dusk="error-berkas">{{ $message }}</div>
                @enderror
            </div>

            <div class="fl mb16">
                <label for="result_link">Tautan hasil pekerjaan</label>
                <div class="in @error('result_link') err @enderror">
                    <i data-lucide="link" class="ic" width="16" height="16"></i>
                    <input
                        type="text"
                        id="result_link"
                        name="result_link"
                        value="{{ old('result_link') }}"
                        dusk="tautan-hasil"
                        placeholder="https://figma.com/file/..."
                        autocomplete="off"
                    >
                </div>
                @error('result_link')
                    <div class="hint err" dusk="error-tautan">{{ $message }}</div>
                @enderror
            </div>

            <div class="fl">
                <label for="description">{{ $isRevision ? 'Deskripsi perubahan' : 'Deskripsi hasil pekerjaan' }} <span style="color:var(--bad)">*</span></label>
                <div class="in @error('description') err @enderror">
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        dusk="deskripsi-hasil"
                        placeholder="{{ $isRevision ? 'Jelaskan perubahan yang dilakukan sesuai catatan revisi.' : 'Jelaskan isi hasil pekerjaan yang diserahkan.' }}"
                    >{{ old('description') }}</textarea>
                </div>
                <div class="ps-counter">
                    @error('description')
                        <div class="hint err" dusk="error-deskripsi">{{ $message }}</div>
                    @else
                        <div class="hint">Isi minimal salah satu: berkas atau tautan. Versi yang sudah dikirim tidak dapat diubah.</div>
                    @enderror
                    <div class="hint" dusk="hitung-deskripsi"><span id="ps-desc-count">0</span>/{{ $maxDesc }}</div>
                </div>
            </div>

            <div class="ps-actions">
                <button type="submit" class="btn p" id="btn-kirim-penyerahan" dusk="kirim-penyerahan">
                    <i data-lucide="send" class="ic" width="16" height="16"></i>
                    <span id="btn-kirim-label">Kirim Versi {{ $form['nextVersion'] }}</span>
                </button>
            </div>
        </form>
    @else
        <div class="ch">
            <i data-lucide="upload" class="ic" width="18" height="18"></i>
            <h3>Penyerahan Hasil Pekerjaan</h3>
        </div>

        <div class="al {{ $infoClass }}" dusk="info-penyerahan">
            <i data-lucide="info" class="ic" width="19" height="19"></i>
            <div>
                <b>{{ $form['message'] }}</b>
                @switch($form['blocker'])
                    @case('submitted')
                        Anda akan dapat mengirim versi perbaikan bila UMKM meminta revisi.
                        @break
                    @case('approved')
                        Penyerahan baru tidak diperlukan.
                        @break
                    @default
                        Riwayat versi tetap dapat dilihat.
                @endswitch
            </div>
        </div>
    @endif
</section>

@if ($form['canSubmit'])
    @push('scripts')
    <script>
    (() => {
        const form = document.getElementById('form-penyerahan');
        if (!form) return;

        const MAX_DESC = {{ $maxDesc }};
        const MAX_BYTES = {{ $rules::MAX_FILE_KB * 1024 }};
        const EXTENSIONS = @json(array_keys($rules::MIME_BY_EXTENSION));
        const DEFAULT_TITLE = 'Seret file ke sini atau pilih file';

        const input = document.getElementById('berkas_hasil');
        const drop = document.getElementById('ps-drop');
        const dropTitle = document.getElementById('ps-drop-title');
        const box = document.getElementById('ps-file');
        const nameEl = document.getElementById('ps-file-name');
        const metaEl = document.getElementById('ps-file-meta');
        const markEl = document.getElementById('ps-file-mark');
        const removeBtn = document.getElementById('ps-file-remove');
        const desc = document.getElementById('description');
        const count = document.getElementById('ps-desc-count');
        const button = document.getElementById('btn-kirim-penyerahan');
        const buttonLabel = document.getElementById('btn-kirim-label');

        const size = bytes => bytes >= 1048576
            ? (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB'
            : (bytes >= 1024 ? (bytes / 1024).toFixed(1).replace('.', ',') + ' KB' : bytes + ' B');

        // Pemeriksaan cepat di browser; server tetap menjadi penentu (SubmissionRequest).
        const problem = file => {
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (file.size === 0) return 'Berkas kosong';
            if (!EXTENSIONS.includes(ext)) return 'Format tidak didukung';
            if (file.size > MAX_BYTES) return 'Melebihi 25 MB';
            return null;
        };

        const renderFile = () => {
            const file = input.files && input.files[0];
            if (!file) {
                box.hidden = true;
                dropTitle.textContent = DEFAULT_TITLE;
                return;
            }
            const issue = problem(file);
            box.hidden = false;
            dropTitle.textContent = 'Ganti berkas';
            nameEl.textContent = file.name;
            metaEl.textContent = size(file.size) + ' · ' + (issue ? issue : 'valid');
            markEl.className = 'mark ' + (issue ? 'bad' : 'ok');
            markEl.innerHTML = issue
                ? '<i data-lucide="triangle-alert" class="ic" width="18" height="18"></i>'
                : '<i data-lucide="circle-check" class="ic" width="18" height="18"></i>';
            if (window.lucide) window.lucide.createIcons();
        };

        input.addEventListener('change', renderFile);
        removeBtn.addEventListener('click', () => {
            input.value = '';
            renderFile();
        });

        ['dragenter', 'dragover'].forEach(evt => drop.addEventListener(evt, e => {
            e.preventDefault();
            drop.classList.add('dragover');
        }));
        ['dragleave', 'drop'].forEach(evt => drop.addEventListener(evt, e => {
            e.preventDefault();
            drop.classList.remove('dragover');
        }));
        drop.addEventListener('drop', e => {
            if (e.dataTransfer && e.dataTransfer.files.length) {
                const one = new DataTransfer();
                one.items.add(e.dataTransfer.files[0]);
                input.files = one.files;
                renderFile();
            }
        });

        // Penghitung karakter: dihitung per karakter (bukan byte) setelah spasi tepi dibuang, sama seperti server.
        const renderCount = () => { count.textContent = Array.from(desc.value.trim()).length; };
        desc.addEventListener('input', renderCount);
        renderCount();

        // Status mengunggah: cegah klik ganda.
        form.addEventListener('submit', () => {
            button.disabled = true;
            buttonLabel.textContent = 'Mengirim…';
        });

        renderFile();
    })();
    </script>
    @endpush
@endif
