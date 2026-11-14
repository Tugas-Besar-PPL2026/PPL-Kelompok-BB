{{--
    [PBI-17] Satu kartu lamaran. Variabel: $item (hasil CandidateApplicationService::present).

    TITIK INTEGRASI dengan PBI lain (tidak diimplementasikan di sini):
      - PBI-25 (Tanggapi Kesepakatan): buat partial resources/views/kandidat/lamaran/_tanggapi-kesepakatan.blade.php.
        Dipanggil hanya untuk lamaran berstatus Diterima. Variabel tersedia: $application.
      - PBI-18 (Batalkan Lamaran): buat partial resources/views/kandidat/lamaran/_batalkan.blade.php.
        Dipanggil hanya bila $application->isCancellable() (Menunggu Keputusan / Terpilih Sementara). Variabel: $application.
      - PBI-14: tombol Detail Proyek muncul otomatis begitu route kandidat.proyek.show tersedia.
    Selama partial/route itu belum ada, bagian tersebut tidak ditampilkan (tidak ada tombol mati dan tidak error).
--}}
@php
    use App\Enums\ApplicationStatus;

    $application = $item['application'];
    $project = $item['project'];
    $detailUrl = Route::has('kandidat.proyek.show') ? route('kandidat.proyek.show', $project) : null;
@endphp

<article class="card la-card" id="lamaran-{{ $application->id }}">
    <div class="la-top">
        <div class="la-main">
            <div class="la-meta">
                <span class="bd {{ $application->status->badge() }}">{{ $application->status->label() }}</span>
                <span class="sub">Dilamar {{ $item['appliedAt'] }}</span>
            </div>
            <h3 class="la-title">{{ $project->title }}</h3>
            <div class="sub">{{ $item['umkm'] }}</div>
        </div>

        <div class="la-actions">
            @if ($application->status === ApplicationStatus::Accepted)
                @includeIf('kandidat.lamaran._tanggapi-kesepakatan', ['application' => $application])
            @endif

            @if ($application->isCancellable())
                @includeIf('kandidat.lamaran._batalkan', ['application' => $application])
            @endif

            @if ($detailUrl)
                <a href="{{ $detailUrl }}" class="btn o sm">
                    <i data-lucide="eye" class="ic" width="16" height="16"></i>Detail Proyek
                </a>
            @endif
        </div>
    </div>

    @if ($item['note'])
        <div class="la-note"><strong>Catatan seleksi:</strong> {{ $item['note'] }}</div>
    @endif

    @if ($application->status === ApplicationStatus::Cancelled)
        <p class="sub la-hint">
            @if ($item['cancelledAt'])
                Dibatalkan oleh Anda pada {{ $item['cancelledAt'] }}.
            @else
                Lamaran ini telah dibatalkan.
            @endif
        </p>
    @elseif ($application->status === ApplicationStatus::Accepted)
        <p class="sub la-hint">Lamaran yang sudah diterima atau ditolak tidak dapat dibatalkan.</p>
    @endif

    @include('kandidat.lamaran._riwayat', ['item' => $item])
</article>
