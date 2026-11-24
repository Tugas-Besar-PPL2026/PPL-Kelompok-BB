{{--
    [PBI-25] Tombol "Tanggapi Kesepakatan" pada kartu Lamaran Saya (titik integrasi yang disediakan PBI-17).
    Dipanggil hanya untuk lamaran berstatus Diterima. Variabel: $application.
    Tampil bila UMKM sudah mengirim kesepakatan dan kesepakatan masih menunggu tanggapan kandidat.
--}}
@php
    $agreement = $application->agreement;
@endphp

@if ($agreement && $agreement->status === \App\Enums\AgreementStatus::Sent)
    <a href="{{ route('kandidat.kesepakatan.tanggapi', $agreement) }}" class="btn p sm" dusk="tanggapi-kesepakatan-{{ $application->id }}">
        <i data-lucide="file-signature" class="ic" width="15" height="15"></i>Tanggapi Kesepakatan
    </a>
@endif
