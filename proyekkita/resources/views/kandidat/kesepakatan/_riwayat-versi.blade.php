{{--
    [PBI-31 / BR-31-14] Riwayat Versi (baca saja): seluruh versi kesepakatan, terbaru di atas.
    Variabel: $history (SubmissionService::present), $form.
    Semua teks dari pengguna ditampilkan lewat {{ }} sehingga di-escape (BR-31-15).
--}}
@php
    $dot = ['info' => '', 'ok' => 'g', 'warn' => 'w'];
@endphp

<section class="card" dusk="riwayat-versi">
    <div class="ch">
        <i data-lucide="history" class="ic" width="18" height="18"></i>
        <h3>Riwayat Versi</h3>
    </div>

    <div class="tl rv-list">
        @if ($form['canSubmit'])
            <div class="it w rv-item" dusk="versi-disiapkan">
                <div class="t1">Versi {{ $form['nextVersion'] }}</div>
                <div class="t2">Sedang disiapkan</div>
            </div>
        @endif

        @foreach ($history as $item)
            <div class="it {{ $dot[$item['statusBadge']] ?? '' }} rv-item" dusk="versi-{{ $item['version'] }}">
                <div class="t1">
                    Versi {{ $item['version'] }}
                    <span class="bd {{ $item['statusBadge'] }}" dusk="status-versi-{{ $item['version'] }}">{{ $item['statusLabel'] }}</span>
                </div>

                @if ($item['submittedAt'])
                    <div class="t2" dusk="waktu-versi-{{ $item['version'] }}">Diserahkan {{ $item['submittedAt'] }}</div>
                @endif

                @if ($item['hasFile'])
                    <div class="rv-file">
                        <i data-lucide="paperclip" class="ic" width="14" height="14"></i>
                        @if ($item['fileUrl'])
                            <a href="{{ $item['fileUrl'] }}" target="_blank" rel="noopener noreferrer" dusk="berkas-versi-{{ $item['version'] }}">{{ $item['fileName'] }}</a>
                            <span class="sub">· {{ $item['fileSize'] }}</span>
                        @else
                            <span dusk="berkas-versi-{{ $item['version'] }}">{{ $item['fileName'] }}</span>
                            <span class="sub" dusk="berkas-hilang-{{ $item['version'] }}">· berkas tidak tersedia</span>
                        @endif
                    </div>
                @endif

                @if ($item['link'])
                    <div class="rv-link">
                        <i data-lucide="link" class="ic" width="14" height="14"></i>
                        <a href="{{ $item['link'] }}" target="_blank" rel="noopener noreferrer" dusk="tautan-versi-{{ $item['version'] }}">{{ $item['link'] }}</a>
                    </div>
                @endif

                <div class="t3" dusk="deskripsi-versi-{{ $item['version'] }}">{{ $item['description'] }}</div>

                @if ($item['note'])
                    <div class="rv-note" dusk="catatan-versi-{{ $item['version'] }}">Catatan UMKM: {{ $item['note'] }}</div>
                @endif
            </div>
        @endforeach
    </div>

    @if (count($history) === 0)
        <p class="sub" dusk="riwayat-kosong">Belum ada hasil pekerjaan yang diserahkan.</p>
    @endif
</section>
