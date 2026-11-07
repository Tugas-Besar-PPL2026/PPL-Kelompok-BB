{{--
    Kartu Pengalaman Kerja pada tab Keterampilan & Pengalaman (PBI-04).
    Kandidat dapat melihat daftar pengalaman kerja beserta total durasi,
    menambahkan pengalaman baru, mengubah pengalaman, dan menghapus pengalaman.
--}}
@php
    $editingExp = $editingExp ?? null;

    $totalMonths = 0;
    foreach ($experiences as $exp) {
        $dur = (string) $exp->duration;
        if (preg_match('/(\d+)\s*(?:tahun|thn)/i', $dur, $m)) {
            $totalMonths += ((int) $m[1]) * 12;
        }
        if (preg_match('/(\d+)\s*(?:bulan|bln)/i', $dur, $m)) {
            $totalMonths += (int) $m[1];
        }
    }

    if ($totalMonths > 0) {
        $years = intdiv($totalMonths, 12);
        $months = $totalMonths % 12;
        $parts = [];
        if ($years > 0) {
            $parts[] = $years . ' thn';
        }
        if ($months > 0) {
            $parts[] = $months . ' bln';
        }
        $totalDurationLabel = 'Total ' . implode(' ', $parts);
    } else {
        $totalDurationLabel = $experiences->count() . ' pengalaman';
    }
@endphp

<section class="card" id="bagian-pengalaman">
    <div class="ch">
        <i data-lucide="briefcase" class="ic" width="20" height="20"></i>
        <h3>Pengalaman Kerja</h3>
        <div class="r">
            <span class="sub" id="exp-total-duration">{{ $totalDurationLabel }}</span>
        </div>
    </div>

    <div id="experience-list">
        @forelse ($experiences as $exp)
            <div class="exp-item">
                <div class="av">
                    <i data-lucide="briefcase" class="ic" width="17" height="17"></i>
                </div>
                <div class="exp-body">
                    <div class="b">{{ $exp->work_type }}</div>
                    <div style="margin-top:5px">
                        <span class="bd grey">
                            <i data-lucide="clock" class="ic" width="12" height="12"></i>{{ $exp->duration }}
                        </span>
                    </div>
                    @if (filled($exp->description))
                        <p class="exp-desc">{{ $exp->description }}</p>
                    @endif
                </div>
                <div class="exp-act">
                    <a
                        href="{{ route('kandidat.profil.edit', ['ubah_pengalaman' => $exp->id]) }}#form-pengalaman"
                        class="ic-btn js-edit-exp"
                        title="Ubah pengalaman"
                        aria-label="Ubah {{ $exp->work_type }}"
                        data-id="{{ $exp->id }}"
                        data-work-type="{{ $exp->work_type }}"
                        data-duration="{{ $exp->duration }}"
                        data-description="{{ $exp->description }}"
                        data-update-url="{{ route('kandidat.profil.pengalaman.update', $exp) }}"
                    >
                        <i data-lucide="pencil" class="ic" width="15" height="15"></i>
                    </a>
                    <form method="POST" action="{{ route('kandidat.profil.pengalaman.destroy', $exp) }}" style="display:inline-flex">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ic-btn" title="Hapus pengalaman" aria-label="Hapus {{ $exp->work_type }}">
                            <i data-lucide="trash-2" class="ic" width="15" height="15"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="sub" style="padding:4px 0 12px">Belum ada pengalaman kerja yang ditambahkan.</div>
        @endforelse
    </div>

    <form
        id="form-pengalaman"
        class="exp-form-box"
        method="POST"
        action="{{ $editingExp ? route('kandidat.profil.pengalaman.update', $editingExp) : route('kandidat.profil.pengalaman.store') }}"
        data-store-url="{{ route('kandidat.profil.pengalaman.store') }}"
    >
        @csrf
        <input
            type="hidden"
            name="_method"
            id="exp-form-method"
            value="PUT"
            @unless ($editingExp) disabled @endunless
        >

        <div class="b" id="exp-form-title" style="font-size:13.5px;margin-bottom:14px">
            {{ $editingExp ? 'Ubah pengalaman' : 'Tambah pengalaman' }}
        </div>

        <div class="form c2">
            <div class="fl">
                <label for="work_type">Bidang / jenis pekerjaan <i>*</i></label>
                <div class="in @error('work_type') err @enderror">
                    <input
                        type="text"
                        id="work_type"
                        name="work_type"
                        maxlength="100"
                        value="{{ old('work_type', $editingExp?->work_type) }}"
                        placeholder="Contoh: Admin media sosial toko online"
                    >
                </div>
                @error('work_type')
                    <div class="hint err">{{ $message }}</div>
                @enderror
            </div>

            <div class="fl">
                <label for="duration">Durasi <i>*</i></label>
                <div class="in @error('duration') err @enderror">
                    <input
                        type="text"
                        id="duration"
                        name="duration"
                        maxlength="50"
                        value="{{ old('duration', $editingExp?->duration) }}"
                        placeholder="Contoh: 3 bulan"
                    >
                </div>
                @error('duration')
                    <div class="hint err">{{ $message }}</div>
                @enderror
            </div>

            <div class="fl span2">
                <label for="exp_description">Deskripsi pekerjaan</label>
                <div class="in ta @error('description') err @enderror">
                    <textarea
                        id="exp_description"
                        name="description"
                        rows="3"
                        placeholder="Jelaskan tugas dan hasil yang Anda kerjakan"
                    >{{ old('description', $editingExp?->description) }}</textarea>
                </div>
                @error('description')
                    <div class="hint err">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
            <a
                href="{{ route('kandidat.profil.edit') }}"
                id="btn-batal-exp"
                class="btn o"
                style="{{ $editingExp ? '' : 'display:none' }}"
            >Batal</a>
            <button type="submit" class="btn p" id="btn-submit-exp">
                <i data-lucide="plus" class="ic" width="16" height="16"></i>
                <span id="btn-submit-exp-label">{{ $editingExp ? 'Simpan Perubahan' : 'Simpan Pengalaman' }}</span>
            </button>
        </div>
    </form>
</section>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('form-pengalaman');
    const methodInput = document.getElementById('exp-form-method');
    const titleEl = document.getElementById('exp-form-title');
    const workTypeInput = document.getElementById('work_type');
    const durationInput = document.getElementById('duration');
    const descInput = document.getElementById('exp_description');
    const cancelBtn = document.getElementById('btn-batal-exp');
    const submitLabel = document.getElementById('btn-submit-exp-label');

    if (!form) return;

    document.querySelectorAll('.js-edit-exp').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            form.action = btn.getAttribute('data-update-url');
            methodInput.disabled = false;
            methodInput.value = 'PUT';
            titleEl.textContent = 'Ubah pengalaman';
            if (submitLabel) submitLabel.textContent = 'Simpan Perubahan';
            if (cancelBtn) cancelBtn.style.display = '';

            workTypeInput.value = btn.getAttribute('data-work-type') || '';
            durationInput.value = btn.getAttribute('data-duration') || '';
            descInput.value = btn.getAttribute('data-description') || '';

            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            workTypeInput.focus();
        });
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', e => {
            e.preventDefault();
            form.action = form.getAttribute('data-store-url');
            methodInput.disabled = true;
            titleEl.textContent = 'Tambah pengalaman';
            if (submitLabel) submitLabel.textContent = 'Simpan Pengalaman';
            cancelBtn.style.display = 'none';

            workTypeInput.value = '';
            durationInput.value = '';
            descInput.value = '';
        });
    }
})();
</script>
@endpush
