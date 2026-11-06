{{--
    Isian proyek yang dipakai bersama oleh halaman Buat Proyek dan Ubah Draf.
    Variabel: $categories, $skills, dan $project (hanya ada di halaman Ubah).
--}}
@php
    $p = $project ?? null;
    $picked = old('skills', $p?->skills->pluck('id')->all() ?? []);
@endphp

<section class="card">
    <div class="ch"><i data-lucide="file-text" class="ic" width="20" height="20"></i><h3>Informasi Proyek</h3></div>

    <div class="form c2">
        <div class="fl span2">
            <label for="title">Judul proyek <i>*</i></label>
            <div class="in @error('title') err @enderror">
                <input id="title" name="title" maxlength="150" value="{{ old('title', $p?->title) }}" placeholder="Contoh: Redesain Katalog Digital & Label Kemasan">
            </div>
            @error('title') <div class="hint err">{{ $message }}</div> @enderror
        </div>

        <div class="fl">
            <label for="category_id">Kategori <i>*</i></label>
            <div class="in @error('category_id') err @enderror">
                <i data-lucide="folder" class="ic" width="16" height="16"></i>
                <select id="category_id" name="category_id">
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $p?->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <i data-lucide="chevron-down" class="ic" width="16" height="16"></i>
            </div>
            @error('category_id') <div class="hint err">{{ $message }}</div> @enderror
        </div>

        <div class="fl">
            <label>Keterampilan yang dibutuhkan <i>*</i></label>
            <div class="in picks @error('skills') err @enderror">
                @foreach ($skills as $skill)
                    <label class="pick">
                        <input type="checkbox" name="skills[]" value="{{ $skill->id }}" @checked(in_array($skill->id, $picked))>
                        <span>{{ $skill->name }}</span><i data-lucide="x" class="ic" width="12" height="12"></i>
                    </label>
                @endforeach
            </div>
            @error('skills') <div class="hint err">{{ $message }}</div> @else <div class="hint">Klik untuk memilih. Dipilih dari data master keterampilan.</div> @enderror
        </div>

        <div class="fl span2">
            <label for="description">Deskripsi pekerjaan <i>*</i></label>
            <div class="in ta @error('description') err @enderror">
                <textarea id="description" name="description" rows="4" placeholder="Jelaskan kebutuhan pekerjaan dan konteks usaha Anda.">{{ old('description', $p?->description) }}</textarea>
            </div>
            @error('description') <div class="hint err">{{ $message }}</div> @enderror
        </div>

        <div class="fl span2">
            <label for="expected_output">Hasil pekerjaan yang diharapkan <i>*</i></label>
            <div class="in ta @error('expected_output') err @enderror">
                <textarea id="expected_output" name="expected_output" rows="4" placeholder="Tulis satu hasil per baris, misalnya: • Desain label kemasan 3 SKU siap cetak">{{ old('expected_output', $p?->expected_output) }}</textarea>
            </div>
            @error('expected_output') <div class="hint err">{{ $message }}</div> @enderror
        </div>
    </div>
</section>

<section class="card">
    <div class="ch"><i data-lucide="calendar-days" class="ic" width="20" height="20"></i><h3>Jadwal &amp; Kuota</h3></div>

    <div class="form c3">
        <div class="fl">
            <label for="application_deadline">Tenggat lamaran <i>*</i></label>
            <div class="in @error('application_deadline') err @enderror">
                <i data-lucide="calendar" class="ic" width="16" height="16"></i>
                <input type="date" id="application_deadline" name="application_deadline" value="{{ old('application_deadline', $p?->application_deadline?->format('Y-m-d')) }}">
            </div>
            @error('application_deadline') <div class="hint err">{{ $message }}</div> @enderror
        </div>

        <div class="fl">
            <label for="target_completion_date">Target penyelesaian <i>*</i></label>
            <div class="in @error('target_completion_date') err @enderror">
                <i data-lucide="calendar-check" class="ic" width="16" height="16"></i>
                <input type="date" id="target_completion_date" name="target_completion_date" value="{{ old('target_completion_date', $p?->target_completion_date?->format('Y-m-d')) }}">
            </div>
            @error('target_completion_date') <div class="hint err">{{ $message }}</div> @enderror
        </div>

        <div class="fl">
            <label for="quota">Kuota kandidat <i>*</i></label>
            <div class="in @error('quota') err @enderror">
                <input type="number" id="quota" name="quota" min="1" max="1000" value="{{ old('quota', $p?->quota ?? 1) }}">
                <span class="sfx">orang</span>
            </div>
            @error('quota') <div class="hint err">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="hint mt8">Target penyelesaian harus setelah tenggat lamaran.</div>
</section>

<section class="card" id="skema-pembayaran">
    <div class="ch"><i data-lucide="wallet" class="ic" width="20" height="20"></i><h3>Skema Pembayaran</h3></div>

    <div class="form c2">
        <div class="fl">
            <label for="total_compensation">Total kompensasi per kandidat <i>*</i></label>
            <div class="in @error('total_compensation') err @enderror">
                <input type="number" id="total_compensation" name="total_compensation" min="1" max="9999999999" placeholder="2500000" placeholder="2500000" value="{{ old('total_compensation', $p ? (int) $p->total_compensation : '') }}">
                <span class="sfx">IDR</span>
            </div>
            @error('total_compensation') <div class="hint err">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- Pilihan cara pembayaran dan tabel termin ditempatkan di sini. --}}

    <div class="al info mt16">
        <i data-lucide="info" class="ic" width="19" height="19"></i>
        <div>Pembayaran dilakukan langsung di luar aplikasi (transfer atau tunai). ProyekKita hanya mencatat pembayaran setiap termin dan konfirmasi penerimaan dari kandidat.</div>
    </div>
</section>
