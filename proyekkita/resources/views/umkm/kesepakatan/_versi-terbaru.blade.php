{{--
    [PBI-32] Versi terbaru yang diserahkan kandidat: berkas (unduh), tautan, dan deskripsi.
    Variabel: $latest (SubmissionService::present dengan route UMKM) atau null.
--}}
<section class="card" dusk="versi-terbaru">
    <div class="ch">
        <i data-lucide="package-open" class="ic" width="18" height="18"></i>
        <h3>{{ $latest ? 'Penyerahan Versi '.$latest['version'] : 'Penyerahan Hasil' }}</h3>
    </div>

    @if (! $latest)
        <p class="sub" dusk="belum-ada-penyerahan">Kandidat belum menyerahkan hasil pekerjaan.</p>
    @else
        @if ($latest['submittedAt'])
            <div class="sub" dusk="waktu-versi-terbaru">Diserahkan {{ $latest['submittedAt'] }}</div>
        @endif

        @if ($latest['hasFile'])
            <div class="tr-item">
                <div class="fi"><i data-lucide="file-text" class="ic" width="18" height="18"></i></div>
                <div style="min-width:0">
                    <div class="nm" dusk="nama-berkas-terbaru">{{ $latest['fileName'] }}</div>
                    <div class="sub">{{ $latest['fileUrl'] ? $latest['fileSize'] : 'Berkas tidak tersedia' }}</div>
                </div>
                @if ($latest['fileUrl'])
                    <a href="{{ $latest['fileUrl'] }}" class="go" target="_blank" rel="noopener noreferrer"
                       dusk="unduh-berkas-terbaru" aria-label="Unduh {{ $latest['fileName'] }}">
                        <i data-lucide="download" class="ic" width="18" height="18"></i>
                    </a>
                @endif
            </div>
        @endif

        @if ($latest['link'])
            <div class="tr-item">
                <div class="fi"><i data-lucide="link" class="ic" width="18" height="18"></i></div>
                <div style="min-width:0">
                    <div class="nm" dusk="tautan-terbaru">{{ $latest['link'] }}</div>
                    <div class="sub">Tautan hasil pekerjaan</div>
                </div>
                <a href="{{ $latest['link'] }}" class="go" target="_blank" rel="noopener noreferrer"
                   dusk="buka-tautan-terbaru" aria-label="Buka tautan hasil pekerjaan">
                    <i data-lucide="external-link" class="ic" width="18" height="18"></i>
                </a>
            </div>
        @endif

        <div class="tr-desc">
            <div class="lb">{{ $latest['version'] > 1 ? 'Deskripsi perubahan dari kandidat' : 'Deskripsi hasil dari kandidat' }}</div>
            <div class="tx" dusk="deskripsi-terbaru">{{ $latest['description'] }}</div>
        </div>
    @endif
</section>
