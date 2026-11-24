{{--
    [PBI-24] Pintu masuk Susun Kesepakatan dari Detail Pelamar (PBI-19). Variabel: $application.
    Tampil hanya untuk lamaran berstatus Diterima:
      - belum ada kesepakatan  -> tombol "Susun Kesepakatan" (membuat draf)
      - sudah ada kesepakatan  -> tautan ke kesepakatan tersebut (Draf: halaman susun; lainnya: detail)
--}}
@php
    use App\Enums\AgreementStatus;
    use App\Enums\ApplicationStatus;

    $agreement = $application->agreement;
@endphp

@if ($application->status === ApplicationStatus::Accepted)
    <section class="card" dusk="panel-kesepakatan">
        <div class="ch"><i data-lucide="handshake" class="ic" width="20" height="20"></i><h3>Kesepakatan Pekerjaan</h3></div>

        @if (! $agreement)
            <p class="sub">Pelamar sudah diterima. Susun kesepakatan pekerjaan lalu kirim kepada kandidat untuk disetujui.</p>
            <form method="POST" action="{{ route('umkm.kesepakatan.store', $application) }}" class="mt12">
                @csrf
                <button type="submit" class="btn p" dusk="susun-kesepakatan">
                    <i data-lucide="file-pen-line" class="ic" width="16" height="16"></i>Susun Kesepakatan
                </button>
            </form>
        @else
            <div class="between">
                <span class="sub">Status kesepakatan</span>
                <span class="bd {{ $agreement->status->badge() }}" dusk="status-kesepakatan-pelamar">{{ $agreement->status->label() }}</span>
            </div>
            <a href="{{ $agreement->status === AgreementStatus::Draft ? route('umkm.kesepakatan.edit', $agreement) : route('umkm.kesepakatan.show', $agreement) }}"
               class="btn ob mt12" dusk="buka-kesepakatan">
                <i data-lucide="file-text" class="ic" width="16" height="16"></i>Buka Kesepakatan
            </a>
        @endif
    </section>
@endif
