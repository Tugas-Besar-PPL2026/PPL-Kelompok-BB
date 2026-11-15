{{--
    Daftar kartu portofolio dan form Tambah/Ubah Portofolio kandidat (PBI-05).
--}}
@php
    $editingPortfolio = $editingPortfolio ?? null;
@endphp

@if ($portfolioItems->isNotEmpty())
    <div class="pf-cards" id="portfolio-list">
        @foreach ($portfolioItems as $item)
            <article class="pf-card">
                <div class="pf-thumb">
                    <i data-lucide="image" class="ic" width="28" height="28"></i>
                </div>
                <div class="pf-body">
                    <div class="pf-title">{{ $item['title'] }}</div>
                    @if (filled($item['description']))
                        <div class="pf-desc">{{ $item['description'] }}</div>
                    @endif

                    <div class="pf-meta">
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" class="pf-link">
                                <i data-lucide="link-2" class="ic" width="13" height="13"></i>
                                <span>{{ $item['url_label'] }}</span>
                            </a>
                        @endif

                        @if ($item['has_file'])
                            <div class="pf-link">
                                <i data-lucide="file-text" class="ic" width="13" height="13"></i>
                                @if ($item['file_available'])
                                    <a href="{{ route('kandidat.profil.portofolio.file', $item['model']) }}" target="_blank" rel="noopener">
                                        {{ $item['file_name'] }}{{ $item['file_size'] ? ' · ' . $item['file_size'] : '' }}
                                    </a>
                                @else
                                    <span>{{ $item['file_name'] }}{{ $item['file_size'] ? ' · ' . $item['file_size'] : '' }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="pf-actions">
                        <a
                            href="{{ route('kandidat.profil.portofolio', ['ubah_portofolio' => $item['id']]) }}#form-portofolio"
                            class="ic-btn js-edit-pf"
                            title="Ubah portofolio"
                            aria-label="Ubah {{ $item['title'] }}"
                            data-id="{{ $item['id'] }}"
                            data-title="{{ $item['title'] }}"
                            data-link="{{ $item['link'] }}"
                            data-description="{{ $item['description'] }}"
                            data-file-name="{{ $item['file_name'] }}"
                            data-update-url="{{ route('kandidat.profil.portofolio.update', $item['model']) }}"
                        >
                            <i data-lucide="pencil" class="ic" width="15" height="15"></i>
                        </a>

                        <form method="POST" action="{{ route('kandidat.profil.portofolio.destroy', $item['model']) }}" style="display:inline-flex">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ic-btn" title="Hapus portofolio" aria-label="Hapus {{ $item['title'] }}">
                                <i data-lucide="trash-2" class="ic" width="15" height="15"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif

<section class="card" style="{{ $portfolioItems->isNotEmpty() ? 'margin-top:18px' : '' }}">
    <div class="ch">
        <i data-lucide="folder-plus" class="ic" width="20" height="20"></i>
        <h3 id="pf-form-title">{{ $editingPortfolio ? 'Ubah Portofolio' : 'Tambah Portofolio' }}</h3>
    </div>

    <form
        id="form-portofolio"
        method="POST"
        enctype="multipart/form-data"
        action="{{ $editingPortfolio ? route('kandidat.profil.portofolio.update', $editingPortfolio) : route('kandidat.profil.portofolio.store') }}"
        data-store-url="{{ route('kandidat.profil.portofolio.store') }}"
    >
        @csrf
        <input
            type="hidden"
            name="_method"
            id="pf-form-method"
            value="PUT"
            @unless ($editingPortfolio) disabled @endunless
        >

        <div class="form c2">
            <div class="fl">
                <label for="pf_title">Judul portofolio <i>*</i></label>
                <div class="in @error('title') err @enderror">
                    <input
                        type="text"
                        id="pf_title"
                        name="title"
                        maxlength="150"
                        value="{{ old('title', $editingPortfolio?->title) }}"
                        placeholder="Contoh: Desain label kemasan kopi"
                    >
                </div>
                @error('title')
                    <div class="hint err">{{ $message }}</div>
                @enderror
            </div>

            <div class="fl">
                <label for="pf_link">Tautan portofolio</label>
                <div class="in @error('link') err @enderror">
                    <i data-lucide="link-2" class="ic" width="16" height="16"></i>
                    <input
                        type="text"
                        id="pf_link"
                        name="link"
                        maxlength="255"
                        value="{{ old('link', $editingPortfolio?->link) }}"
                        placeholder="https://"
                    >
                </div>
                @error('link')
                    <div class="hint err">{{ $message }}</div>
                @enderror
            </div>

            <div class="fl span2">
                <label for="pf_description">Deskripsi singkat</label>
                <div class="in ta @error('description') err @enderror">
                    <textarea
                        id="pf_description"
                        name="description"
                        rows="3"
                        placeholder="Ceritakan pekerjaan dan peran Anda"
                    >{{ old('description', $editingPortfolio?->description) }}</textarea>
                </div>
                @error('description')
                    <div class="hint err">{{ $message }}</div>
                @enderror
            </div>

            <div class="fl span2">
                <label for="pf_file">Dokumen portofolio</label>
                <label for="pf_file" class="pf-dropzone @error('file') err @enderror" id="pf-dropzone">
                    <i data-lucide="cloud-upload" class="ic" width="24" height="24" style="color:var(--pri)"></i>
                    <div class="pf-drop-title" id="pf-file-label">
                        {{ $editingPortfolio && filled($editingPortfolio->file_path) ? basename($editingPortfolio->file_path) : 'Seret file ke sini atau pilih file' }}
                    </div>
                    <div class="pf-drop-sub" id="pf-file-sub">PDF, JPG, atau PNG · maksimal 10 MB</div>
                    <input
                        type="file"
                        id="pf_file"
                        name="file"
                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        style="display:none"
                    >
                </label>
                @error('file')
                    <div class="hint err">{{ $message }}</div>
                @else
                    <div class="hint">Isi minimal salah satu: tautan atau dokumen.</div>
                @enderror
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
            <a
                href="{{ route('kandidat.profil.portofolio') }}"
                id="btn-batal-pf"
                class="btn o"
                style="{{ $editingPortfolio ? '' : 'display:none' }}"
            >Batal</a>
            <button type="submit" class="btn p" id="btn-submit-pf">
                <i data-lucide="plus" class="ic" width="16" height="16"></i>
                <span id="btn-submit-pf-label">{{ $editingPortfolio ? 'Simpan Perubahan' : 'Simpan Portofolio' }}</span>
            </button>
        </div>
    </form>
</section>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('form-portofolio');
    const methodInput = document.getElementById('pf-form-method');
    const titleHeader = document.getElementById('pf-form-title');
    const titleInput = document.getElementById('pf_title');
    const linkInput = document.getElementById('pf_link');
    const descInput = document.getElementById('pf_description');
    const fileInput = document.getElementById('pf_file');
    const fileLabel = document.getElementById('pf-file-label');
    const dropzone = document.getElementById('pf-dropzone');
    const cancelBtn = document.getElementById('btn-batal-pf');
    const submitLabel = document.getElementById('btn-submit-pf-label');

    if (!form) return;

    if (fileInput && fileLabel) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0];
            fileLabel.textContent = file ? file.name : 'Seret file ke sini atau pilih file';
        });
    }

    if (dropzone && fileInput) {
        ['dragenter', 'dragover'].forEach(evt => {
            dropzone.addEventListener(evt, e => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(evt => {
            dropzone.addEventListener(evt, e => {
                e.preventDefault();
                dropzone.classList.remove('dragover');
            });
        });
        dropzone.addEventListener('drop', e => {
            if (e.dataTransfer?.files?.length) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }

    document.querySelectorAll('.js-edit-pf').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            form.action = btn.getAttribute('data-update-url');
            methodInput.disabled = false;
            methodInput.value = 'PUT';
            titleHeader.textContent = 'Ubah Portofolio';
            if (submitLabel) submitLabel.textContent = 'Simpan Perubahan';
            if (cancelBtn) cancelBtn.style.display = '';

            titleInput.value = btn.getAttribute('data-title') || '';
            linkInput.value = btn.getAttribute('data-link') || '';
            descInput.value = btn.getAttribute('data-description') || '';

            const existingFile = btn.getAttribute('data-file-name');
            if (fileLabel) {
                fileLabel.textContent = existingFile ? existingFile : 'Seret file ke sini atau pilih file';
            }

            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            titleInput.focus();
        });
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', e => {
            e.preventDefault();
            form.action = form.getAttribute('data-store-url');
            methodInput.disabled = true;
            titleHeader.textContent = 'Tambah Portofolio';
            if (submitLabel) submitLabel.textContent = 'Simpan Portofolio';
            cancelBtn.style.display = 'none';

            titleInput.value = '';
            linkInput.value = '';
            descInput.value = '';
            if (fileInput) fileInput.value = '';
            if (fileLabel) fileLabel.textContent = 'Seret file ke sini atau pilih file';
        });
    }
})();
</script>
@endpush
