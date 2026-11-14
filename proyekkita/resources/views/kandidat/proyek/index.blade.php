{{--
    [PBI-14 + PBI-15] Eksplor Proyek: daftar proyek yang dipublikasikan dan masih menerima lamaran,
    dengan panel pencarian, filter, dan pengurutan di sisi kiri.
    Variabel: $projects (paginator), $discovery (ProjectDiscoveryService), $filters (array, lihat
              ProjectSearchRequest::filters), $filterCount (int), $categories, $skills, $sorts (nilai => label).
--}}
@extends('layouts.app')
@section('title', 'Eksplor Proyek')

@push('styles')
<style>
    .pj-head{display:flex;align-items:center;gap:12px}
    .pj-head .meta{min-width:0}
    .pj-head .nm{font-weight:600;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .pj-head .loc{font-size:12.5px;color:var(--mut);display:flex;gap:4px;align-items:center;margin-top:2px}
    .pj-title{font-size:18px;font-weight:600;margin:14px 0 6px;letter-spacing:-.2px}
    .pj-title a{color:var(--ink)}
    .pj-title a:hover{color:var(--pri)}
    .pj-desc{color:var(--ink2);margin-bottom:12px}
    .pj-foot{display:flex;align-items:center;gap:20px;flex-wrap:wrap;margin-top:16px;padding-top:14px;border-top:1px solid var(--line2)}
    .pj-foot .it .l{font-size:12px;color:var(--mut)}
    .pj-foot .it .v{font-weight:600}
    .pj-foot .btn{margin-left:auto}
    .pj-pager{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:22px}

    /* ----- PBI-15: panel filter ----- */
    .pj-layout{display:grid;grid-template-columns:290px minmax(0,1fr);gap:20px;align-items:start}
    .pj-filter .fl{margin-bottom:14px}
    .pj-filter .two{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .pj-skills{max-height:170px;overflow:auto;display:grid;gap:8px;padding:2px}
    .pj-chk{display:flex;align-items:center;gap:8px;font-size:13.5px;cursor:pointer}
    .pj-filter .acts{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px}
    .pj-count{font-size:13.5px;color:var(--mut);margin-bottom:12px}
    @media (max-width:900px){.pj-layout{grid-template-columns:1fr}}
    @media (max-width:640px){.pj-foot .btn{margin-left:0;width:100%;justify-content:center}}
</style>
@endpush

@section('content')
    @php
        $rp = fn ($n) => 'Rp' . number_format((float) $n, 0, ',', '.');
        // old() terisi hanya setelah validasi filter gagal, selain itu nilai dari $filters.
        $pickedSkills = array_map('intval', (array) old('keterampilan', $filters['keterampilan']));
        $currentSort = (string) old('urut', $filters['urut']);
    @endphp

    <div class="ph">
        <div>
            <h1>Eksplor Proyek</h1>
            <p>Temukan proyek UMKM yang sedang membuka lamaran.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="al bad mb16" dusk="error-filter">
            <i data-lucide="circle-alert" class="ic" width="19" height="19"></i>
            <div>
                <b>Filter belum dapat diterapkan</b>
                @foreach ($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="pj-layout">
        {{-- ===== PBI-15: pencarian, filter, urutan ===== --}}
        <aside>
            <form method="GET" action="{{ route('kandidat.proyek.index') }}" class="card pj-filter" dusk="form-filter">
                <div class="ch">
                    <i data-lucide="sliders-horizontal" class="ic" width="20" height="20"></i>
                    <h3>Cari &amp; Filter</h3>
                </div>

                <div class="fl">
                    <label for="cari">Kata kunci</label>
                    <div class="in">
                        <i data-lucide="search" class="ic" width="16" height="16"></i>
                        <input id="cari" type="text" name="cari" maxlength="100" value="{{ old('cari', $filters['cari']) }}" placeholder="Judul atau deskripsi proyek" dusk="cari-proyek">
                    </div>
                </div>

                <div class="fl">
                    <label for="kategori">Kategori</label>
                    <div class="in">
                        <i data-lucide="folder" class="ic" width="16" height="16"></i>
                        <select id="kategori" name="kategori" dusk="filter-kategori">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('kategori', $filters['kategori']) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="ic" width="16" height="16"></i>
                    </div>
                </div>

                <div class="fl">
                    <label>Keterampilan</label>
                    <div class="pj-skills" dusk="filter-keterampilan">
                        @forelse ($skills as $skill)
                            <label class="pj-chk">
                                <input type="checkbox" name="keterampilan[]" value="{{ $skill->id }}" dusk="filter-keterampilan-{{ $skill->id }}" @checked(in_array((int) $skill->id, $pickedSkills))>
                                <span>{{ $skill->name }}</span>
                            </label>
                        @empty
                            <span class="sub">Belum ada data keterampilan.</span>
                        @endforelse
                    </div>
                    <div class="hint">Menampilkan proyek yang membutuhkan salah satu keterampilan terpilih.</div>
                </div>

                <div class="fl">
                    <label>Rentang kompensasi (Rp)</label>
                    <div class="two">
                        <div class="in"><input type="number" min="0" name="kompensasi_min" value="{{ old('kompensasi_min', $filters['kompensasi_min']) }}" placeholder="Minimum" dusk="filter-kompensasi-min" aria-label="Kompensasi minimum"></div>
                        <div class="in"><input type="number" min="0" name="kompensasi_maks" value="{{ old('kompensasi_maks', $filters['kompensasi_maks']) }}" placeholder="Maksimum" dusk="filter-kompensasi-maks" aria-label="Kompensasi maksimum"></div>
                    </div>
                </div>

                <div class="fl">
                    <label for="tenggat_sebelum">Tenggat lamaran paling lambat</label>
                    <div class="in">
                        <i data-lucide="calendar" class="ic" width="16" height="16"></i>
                        <input id="tenggat_sebelum" type="date" name="tenggat_sebelum" value="{{ old('tenggat_sebelum', $filters['tenggat_sebelum']) }}" dusk="filter-tenggat">
                    </div>
                </div>

                <div class="fl">
                    <label for="urut">Urutkan</label>
                    <div class="in">
                        <i data-lucide="arrow-up-down" class="ic" width="16" height="16"></i>
                        <select id="urut" name="urut" dusk="urutkan">
                            @foreach ($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($currentSort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="ic" width="16" height="16"></i>
                    </div>
                </div>

                <div class="acts">
                    <button type="submit" class="btn p" dusk="terapkan-filter">Terapkan</button>
                    <a href="{{ route('kandidat.proyek.index') }}" class="btn o" dusk="reset-filter">Reset</a>
                </div>
            </form>
        </aside>

        {{-- ===== PBI-14: daftar proyek ===== --}}
        <div>
            <div class="pj-count" dusk="jumlah-hasil">
                {{ $projects->total() }} proyek ditemukan
                @if ($filterCount > 0)
                   · {{ $filterCount }} filter aktif
                @endif
            </div>

            @forelse ($projects as $project)
                @php
                    $umkm = $project->umkmProfile;
                    $initials = collect(preg_split('/\s+/', trim($umkm->business_name)))->filter()->take(2)
                        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
                    $sisa = $discovery->remainingQuota($project, $project->accepted_count);
                @endphp
                <article class="card" dusk="kartu-proyek">
                    <div class="pj-head">
                        <div class="av">{{ $initials }}</div>
                        <div class="meta">
                            <div class="nm">
                                {{ $umkm->business_name }}
                                @if ($umkm->isVerified())
                                    <span class="bd ok"><i data-lucide="badge-check" class="ic" width="13" height="13"></i>UMKM Terverifikasi</span>
                                @endif
                            </div>
                            <div class="loc"><i data-lucide="map-pin" class="ic" width="13" height="13"></i>{{ $umkm->city }} · {{ $umkm->business_field }}</div>
                        </div>
                        <span class="bd info" style="margin-left:auto">{{ $project->category?->name }}</span>
                    </div>

                    <h3 class="pj-title"><a href="{{ route('kandidat.proyek.show', $project) }}" dusk="judul-proyek">{{ $project->title }}</a></h3>
                    <p class="pj-desc">{{ \Illuminate\Support\Str::limit($project->description, 170) }}</p>

                    <div class="tags">
                        @foreach ($project->skills as $skill)
                            <span class="tag">{{ $skill->name }}</span>
                        @endforeach
                    </div>

                    <div class="pj-foot">
                        <div class="it"><div class="l">Total kompensasi</div><div class="v num">{{ $rp($project->total_compensation) }} · {{ $project->payment_terms_count }} termin</div></div>
                        <div class="it"><div class="l">Sisa kuota</div><div class="v">{{ $sisa }} dari {{ $project->quota }} orang</div></div>
                        <div class="it"><div class="l">Tenggat lamaran</div><div class="v">{{ $project->application_deadline->locale('id')->translatedFormat('j M Y') }}</div></div>
                        <a href="{{ route('kandidat.proyek.show', $project) }}" class="btn p" dusk="lihat-detail">Lihat Detail &amp; Lamar<i data-lucide="arrow-right" class="ic" width="16" height="16"></i></a>
                    </div>
                </article>
            @empty
                <div class="card" dusk="hasil-kosong">
                    @if ($filterCount > 0)
                        <div class="al info">
                            <i data-lucide="search-x" class="ic" width="19" height="19"></i>
                            <div><b>Tidak ada proyek yang cocok</b>Coba ubah kata kunci atau longgarkan filter, atau tekan Reset untuk melihat semua proyek.</div>
                        </div>
                    @else
                        <div class="al info">
                            <i data-lucide="info" class="ic" width="19" height="19"></i>
                            <div><b>Belum ada proyek yang terbuka</b>Saat ini belum ada proyek yang menerima lamaran. Silakan cek kembali nanti.</div>
                        </div>
                    @endif
                </div>
            @endforelse

            @if ($projects->hasPages())
                <nav class="pj-pager" aria-label="Halaman">
                    @if ($projects->onFirstPage())
                        <span class="btn o sm dis">Sebelumnya</span>
                    @else
                        <a class="btn o sm" href="{{ $projects->previousPageUrl() }}">Sebelumnya</a>
                    @endif
                    <span class="sub">Halaman {{ $projects->currentPage() }} dari {{ $projects->lastPage() }}</span>
                    @if ($projects->hasMorePages())
                        <a class="btn o sm" href="{{ $projects->nextPageUrl() }}">Berikutnya</a>
                    @else
                        <span class="btn o sm dis">Berikutnya</span>
                    @endif
                </nav>
            @endif
        </div>
    </div>
@endsection
