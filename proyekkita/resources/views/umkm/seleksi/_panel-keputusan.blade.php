{{--
    Panel "Keputusan Seleksi" pada Detail Pelamar (mockup 4.5.20).
    Mockup ini menggabungkan PBI-19, PBI-20, dan PBI-21. Pada PBI-19 panel hanya MENAMPILKAN struktur visualnya
    beserta data nyata (kuota, status saat ini, catatan seleksi tersimpan). Semua kontrol dinonaktifkan dan tidak ada
    form maupun route yang mengubah status, supaya tidak ada perubahan status palsu dan logika PBI-20/PBI-21 milik
    anggota tim lain tidak terduplikasi.

    Titik sambung untuk PBI-20/PBI-21: ganti <div class="..."> pembungkus ini dengan <form> ke route keputusan,
    hilangkan atribut disabled, dan tambahkan @csrf.

    Parameter: $application, $quota (accepted, total, percent, remaining, full).
--}}
@use('App\Enums\ApplicationStatus')

@php
    $current = $application->status;

    $options = [
        [
            'status' => ApplicationStatus::Accepted,
            'title' => 'Terima pelamar',
            'desc' => $quota['full']
                ? 'Kuota sudah penuh. Draf kesepakatan pekerjaan dibuat otomatis.'
                : 'Sisa kuota '.$quota['remaining'].'. Draf kesepakatan pekerjaan dibuat otomatis.',
        ],
        [
            'status' => ApplicationStatus::Rejected,
            'title' => 'Tolak dengan catatan',
            'desc' => 'Catatan wajib diisi dan terlihat oleh kandidat.',
        ],
        [
            'status' => ApplicationStatus::Shortlisted,
            'title' => 'Masukkan ke terpilih sementara',
            'desc' => '',
        ],
    ];

    // Pilihan yang sama dengan status saat ini tampil redup; pilihan pertama yang lain ditandai sebagai sorotan.
    $highlighted = collect($options)->first(fn ($option) => $option['status'] !== $current)['status'] ?? null;
@endphp

<section class="card" aria-labelledby="judul-keputusan">
    <div class="ch"><i data-lucide="gavel" class="ic" width="20" height="20"></i><h3 id="judul-keputusan">Keputusan Seleksi</h3></div>

    <div class="qbox">
        <div class="between"><b>Kuota kandidat</b><span>{{ $quota['accepted'] }} dari {{ $quota['total'] }} terisi</span></div>
        <div class="prog" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $quota['percent'] }}" aria-label="Kuota kandidat terisi">
            <i style="width:{{ $quota['percent'] }}%"></i>
        </div>
    </div>

    <div role="radiogroup" aria-label="Pilihan keputusan seleksi">
        @foreach ($options as $option)
            @php
                $isCurrent = $option['status'] === $current;
                $isSelected = ! $isCurrent && $option['status'] === $highlighted;
            @endphp
            <label class="opt {{ $isCurrent ? 'dis' : '' }} {{ $isSelected ? 'sel' : '' }}">
                <input type="radio" name="keputusan" value="{{ $option['status']->value }}" disabled @checked($isSelected)>
                <span class="dot" aria-hidden="true"></span>
                <span>
                    <span class="tt" style="display:block">{{ $option['title'] }}</span>
                    @if ($isCurrent)
                        <span class="ds" style="display:block">Status saat ini</span>
                    @elseif ($option['desc'] !== '')
                        <span class="ds" style="display:block">{{ $option['desc'] }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>

    <div class="dlabel"><label for="catatan-seleksi">Catatan seleksi</label></div>
    <textarea id="catatan-seleksi" class="dnote" rows="3" disabled placeholder="Belum ada catatan seleksi.">{{ $application->selection_note }}</textarea>
    <div class="hint mt8">Opsional saat menerima, wajib saat menolak.</div>

    <div class="mt16">
        <button type="button" class="btn p" disabled><i data-lucide="check" class="ic" width="16" height="16"></i>Simpan Keputusan</button>
    </div>
    <div class="hint mt12">Keputusan seleksi belum dapat diubah dari halaman ini. Panel menampilkan status lamaran saat ini.</div>
</section>
