{{--
    Kartu Keterampilan pada halaman Profil & Portofolio kandidat (PBI-3).
    Kandidat dapat melihat keterampilan yang sudah dipilih, mencari keterampilan aktif dari data master,
    menambahkan keterampilan baru, serta menghapus keterampilan dari profil.
--}}
@php
    $selectedIds = $selectedSkills->pluck('id');
@endphp

<section class="card" id="bagian-keterampilan">
    <div class="ch">
        <i data-lucide="sparkles" class="ic" width="20" height="20"></i>
        <h3>Keterampilan</h3>
        <div class="r">
            <span class="sub" id="skill-count">{{ $selectedSkills->count() }} dipilih</span>
        </div>
    </div>

    <div class="tags" id="selected-skills">
        @forelse ($selectedSkills as $skill)
            <form method="POST" action="{{ route('kandidat.profil.keterampilan.destroy', $skill) }}" class="tag-form">
                @csrf
                @method('DELETE')
                <input type="hidden" name="q" value="{{ $searchQuery }}" class="js-search-q">
                <span class="tag on">
                    <span>{{ $skill->name }}</span>
                    <button type="submit" class="tag-rm" title="Hapus {{ $skill->name }}" aria-label="Hapus {{ $skill->name }}">
                        <i data-lucide="x" class="ic" width="13" height="13"></i>
                    </button>
                </span>
            </form>
        @empty
            <span class="sub" id="empty-selected-skills">Belum ada keterampilan yang dipilih.</span>
        @endforelse
    </div>

    @error('skill_id')
        <div class="hint err mt8">{{ $message }}</div>
    @enderror
    @error('skills')
        <div class="hint err mt8">{{ $message }}</div>
    @enderror

    <div class="fl mt16">
        <label for="cari-keterampilan">Tambah keterampilan</label>
        <div class="in">
            <i data-lucide="search" class="ic" width="16" height="16"></i>
            <input
                type="text"
                id="cari-keterampilan"
                name="q"
                value="{{ $searchQuery }}"
                placeholder="Cari keterampilan..."
                autocomplete="off"
            >
        </div>

        <div class="skill-box" id="skill-list">
            @foreach ($skills as $skill)
                @php
                    $isPicked = $selectedIds->contains($skill->id);
                @endphp
                <div class="skill-row" data-skill-row data-skill-name="{{ mb_strtolower($skill->name) }}">
                    <span>{{ $skill->name }}</span>
                    @if ($isPicked)
                        <span class="skill-picked">sudah dipilih</span>
                    @else
                        <form method="POST" action="{{ route('kandidat.profil.keterampilan.store') }}">
                            @csrf
                            <input type="hidden" name="skill_id" value="{{ $skill->id }}">
                            <input type="hidden" name="q" value="{{ $searchQuery }}" class="js-search-q">
                            <button type="submit" class="skill-add">+ tambah</button>
                        </form>
                    @endif
                </div>
            @endforeach
            <div class="skill-row sub" id="skill-empty-search" style="display:none">
                <span>Keterampilan tidak ditemukan.</span>
            </div>
        </div>

        <div class="hint">Keterampilan dipilih dari data master yang dikelola admin. Keterampilan nonaktif tidak ditampilkan.</div>
    </div>
</section>

@push('scripts')
<script>
(() => {
    const searchInput = document.getElementById('cari-keterampilan');
    const rows = Array.from(document.querySelectorAll('[data-skill-row]'));
    const emptyRow = document.getElementById('skill-empty-search');
    const hiddenInputs = Array.from(document.querySelectorAll('.js-search-q'));

    if (!searchInput) return;

    function filterSkills() {
        const q = searchInput.value.trim().toLowerCase();
        hiddenInputs.forEach(input => { input.value = searchInput.value.trim(); });

        let visibleCount = 0;
        rows.forEach(row => {
            const name = row.getAttribute('data-skill-name') || '';
            const match = q === '' || name.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    searchInput.addEventListener('input', filterSkills);
    filterSkills();
})();
</script>
@endpush
