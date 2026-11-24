{{--
    [PBI-32] Riwayat Versi (tabel, baca saja). Variabel: $history (SubmissionService::present, terbaru di atas).
--}}
<section class="card" dusk="riwayat-versi-umkm">
    <div class="ch">
        <i data-lucide="history" class="ic" width="18" height="18"></i>
        <h3>Riwayat Versi</h3>
    </div>

    @if (count($history) === 0)
        <p class="sub" dusk="riwayat-kosong-umkm">Belum ada hasil pekerjaan yang diserahkan.</p>
    @else
        <div style="overflow-x:auto">
            <table class="tr-table">
                <thead>
                    <tr><th>Versi</th><th>Diserahkan</th><th>Status</th><th>Catatan UMKM</th></tr>
                </thead>
                <tbody>
                    @foreach ($history as $item)
                        <tr dusk="baris-versi-{{ $item['version'] }}">
                            <td><b>Versi {{ $item['version'] }}</b></td>
                            <td>{{ $item['submittedDate'] ?? '–' }}</td>
                            <td><span class="bd {{ $item['statusBadge'] }}" dusk="status-baris-{{ $item['version'] }}">{{ $item['statusLabel'] }}</span></td>
                            <td class="nt" dusk="catatan-baris-{{ $item['version'] }}">{{ $item['note'] ?? '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
