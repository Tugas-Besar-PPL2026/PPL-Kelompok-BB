{{--
    [PBI-31] Pintu masuk ke halaman Penyerahan Hasil dari kartu lamaran yang diterima dan sudah memiliki kesepakatan.
    Sementara daftar/detail kesepakatan (PBI-26) belum tersedia, tombol ini menjadi navigasi kandidat ke penyerahan hasil.
    Variabel: $application.
--}}
@php
    $agreement = $application->agreement;
@endphp

@if ($agreement)
    <a
        href="{{ route('kandidat.kesepakatan.penyerahan.index', $agreement) }}"
        class="btn o sm"
        dusk="penyerahan-hasil-{{ $application->id }}"
    >
        <i data-lucide="upload" class="ic" width="15" height="15"></i>Penyerahan Hasil
    </a>
@endif
