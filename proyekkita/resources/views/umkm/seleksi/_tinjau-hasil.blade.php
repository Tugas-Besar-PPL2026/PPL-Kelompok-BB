{{--
    [PBI-32] Pintu masuk UMKM ke halaman Tinjau Hasil dari detail pelamar yang diterima dan sudah memiliki kesepakatan.
    Sementara daftar/detail kesepakatan (PBI-26) belum tersedia. Variabel: $application.
--}}
@php
    $agreement = $application->agreement;
@endphp

@if ($agreement)
    <a href="{{ route('umkm.kesepakatan.tinjau.index', $agreement) }}" class="btn p" dusk="tinjau-hasil-{{ $application->id }}">
        <i data-lucide="clipboard-check" class="ic" width="16" height="16"></i>Tinjau Hasil
    </a>
@endif
