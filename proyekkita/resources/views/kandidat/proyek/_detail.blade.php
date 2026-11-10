{{--
    [PBI-14] Kolom kiri halaman Detail Proyek: informasi lengkap proyek dari database.
    Variabel: $project, $outputs, $deadlineNote, $remainingQuota.
--}}
@php
    $rp = fn ($n) => 'Rp' . number_format((float) $n, 0, ',', '.');
    $tgl = fn ($d) => $d?->locale('id')->translatedFormat('j M Y');
    $open = $project->isOpen();
    $statusBadge = match (true) {
        $open => ['ok', 'circle-check', 'Dibuka'],
        $project->status === \App\Enums\ProjectStatus::Closed => ['grey', 'lock', 'Ditutup'],
        default => ['warn', 'clock', 'Tenggat Berakhir'],
    };
    $total = (float) $project->total_compensation;
@endphp

<section class="card">
    <div class="pd-meta">
        <span class="bd {{ $statusBadge[0] }}" dusk="status-proyek"><i data-lucide="{{ $statusBadge[1] }}" class="ic" width="13" height="13"></i>{{ $statusBadge[2] }}</span>
        <span>{{ $project->category?->name }} · Dipublikasikan {{ $tgl($project->published_at) }}</span>
    </div>

    <h1 class="pd-title">{{ $project->title }}</h1>
    <p class="pd-text">{{ $project->description }}</p>

    <h2 class="pd-h">Hasil pekerjaan yang diharapkan</h2>
    <ul class="pd-list">
        @foreach ($outputs as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <h2 class="pd-h">Keterampilan yang dibutuhkan</h2>
    <div class="tags">
        @foreach ($project->skills as $skill)
            <span class="tag">{{ $skill->name }}</span>
        @endforeach
    </div>

    <div class="grid g3 pd-stats">
        <div class="stat"><div class="l">Tenggat lamaran</div><div class="v">{{ $tgl($project->application_deadline) }}</div><div class="s">{{ $deadlineNote }}</div></div>
        <div class="stat"><div class="l">Target penyelesaian</div><div class="v">{{ $tgl($project->target_completion_date) }}</div><div class="s">setelah kesepakatan aktif</div></div>
        <div class="stat"><div class="l">Kuota kandidat</div><div class="v">{{ $project->quota }} orang</div><div class="s">sisa {{ $remainingQuota }} · dipilih oleh UMKM</div></div>
    </div>

    <div class="pd-comp">
        <h2 class="pd-h" style="margin:0">Kompensasi &amp; skema pembayaran</h2>
        <div class="total num">{{ $rp($total) }}</div>
    </div>

    <div class="tbl-wrap">
        <table class="t">
            <thead>
                <tr><th>Termin</th><th>Kondisi / target pembayaran</th><th>Nominal</th><th>Porsi</th></tr>
            </thead>
            <tbody>
                @forelse ($project->paymentTerms as $term)
                    <tr>
                        <td class="b">Termin {{ $term->term_number }}</td>
                        <td>{{ $term->condition }}</td>
                        <td class="b num">{{ $rp($term->amount) }}</td>
                        <td class="mut num">{{ $total > 0 ? round((float) $term->amount / $total * 100) : 0 }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="mut">Skema pembayaran belum dicantumkan.</td></tr>
                @endforelse
                <tr class="sum">
                    <td>Total</td><td></td><td class="num">{{ $rp($total) }}</td><td class="num">100%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="al info mt16">
        <i data-lucide="info" class="ic" width="19" height="19"></i>
        <div>Pembayaran dilakukan langsung oleh UMKM di luar aplikasi. Setiap termin dicatat di ProyekKita dan Anda dapat mengonfirmasi penerimaannya.</div>
    </div>
</section>
