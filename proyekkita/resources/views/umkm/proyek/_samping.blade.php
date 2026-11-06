{{--
    Panel kanan halaman Buat Proyek dan Ubah Draf: kelengkapan isian dan alur publikasi.
    Centang kelengkapan diperbarui langsung di browser setiap kali form diisi.
--}}
@php
    $isVerified = (bool) auth()->user()->umkmProfile?->isVerified();
    $needsRevision = isset($project) && $project->status === \App\Enums\ProjectStatus::RevisionRequested;
@endphp

<section class="card">
    <div class="ch"><i data-lucide="list-checks" class="ic" width="20" height="20"></i><h3>Kelengkapan Draf</h3></div>

    @foreach ([
        'akun' => 'Akun UMKM terverifikasi',
        'info' => 'Judul, kategori, dan deskripsi',
        'hasil' => 'Hasil pekerjaan & keterampilan',
        'jadwal' => 'Jadwal dan kuota kandidat',
        'bayar' => 'Total kompensasi diisi',
    ] as $key => $label)
        <div class="row" style="padding:7px 0">
            <span class="chk {{ $key === 'akun' && $isVerified ? 'on' : '' }}" data-cek="{{ $key }}"><i data-lucide="check" class="ic" width="13" height="13"></i></span>
            <span>{{ $label }}</span>
        </div>
    @endforeach

    @unless ($isVerified)
        <div class="hr"></div>
        <div class="sub">Draf tetap bisa disimpan. Pengajuan ke admin baru bisa dilakukan setelah akun UMKM terverifikasi.</div>
    @endunless
</section>

<section class="card">
    <div class="ch"><i data-lucide="route" class="ic" width="20" height="20"></i><h3>Alur Publikasi</h3></div>

    <div class="tl">
        <div class="it {{ $needsRevision ? 'w' : 'g' }}">
            <div class="t1">Draf</div>
            <div class="t2">{{ $needsRevision ? 'Perlu perbaikan dari admin' : 'Sedang diisi' }}</div>
        </div>
        <div class="it">
            <div class="t1">Menunggu moderasi admin</div>
            <div class="t2">Setelah diajukan</div>
        </div>
        <div class="it">
            <div class="t1">Dipublikasikan</div>
            <div class="t2">Tampil di Eksplor Proyek</div>
        </div>
    </div>
</section>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('form-proyek');
    if (!form) return;

    const val = name => (form.elements[name]?.value || '').trim();
    const rules = {
        info: () => val('title') && val('category_id') && val('description'),
        hasil: () => val('expected_output') && form.querySelector('[name="skills[]"]:checked'),
        jadwal: () => val('application_deadline') && val('target_completion_date') && Number(val('quota')) > 0,
        bayar: () => Number(val('total_compensation')) > 0,
    };

    function refresh() {
        for (const [key, rule] of Object.entries(rules)) {
            document.querySelector('[data-cek="' + key + '"]')?.classList.toggle('on', Boolean(rule()));
        }
    }

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();
})();
</script>
@endpush
