{{--
    [PBI-31 / FR-14] Halaman Penyerahan Hasil Pekerjaan (Kandidat).
    Variabel (SubmissionService::page): $agreement, $project, $code, $umkmName, $candidateName,
    $deadline, $deadlinePassed, $stages, $form, $history.

    Bagian halaman:
      - _ringkasan-kesepakatan : kode, status, judul proyek, UMKM, tenggat, linimasa 5 tahap
      - _form-penyerahan       : form Kirim Hasil Pekerjaan (Versi 1) atau kartu info bila tidak dapat mengirim
      - _riwayat-versi         : Riwayat Versi (baca saja)
    Gaya halaman dipindah ke partial bersama kesepakatan._gaya (dipakai juga halaman Tinjau Hasil UMKM, PBI-32).
    Versi perbaikan (PBI-33) dipasang lewat partial kandidat.kesepakatan._form-perbaikan bila sudah dibuat.
--}}
@extends('layouts.app')
@section('title', 'Penyerahan Hasil Pekerjaan')

@push('styles')
@include('kesepakatan._gaya')
@endpush

@section('content')
    <div dusk="halaman-penyerahan">
        <div class="crumb">
            <span>Kesepakatan &amp; Progres</span>
            <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
            <span>{{ $code }}</span>
            <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
            <span>Penyerahan Hasil</span>
        </div>
        <div class="ph">
            <div>
                <h1>Penyerahan Hasil Pekerjaan</h1>
                <p>Serahkan hasil sesuai kesepakatan. Setiap penyerahan diberi nomor versi.</p>
            </div>
        </div>

        @include('kandidat.kesepakatan._ringkasan-kesepakatan')

        <div class="grid g21 ps-grid">
            <div>@include('kandidat.kesepakatan._form-penyerahan')</div>
            <div>@include('kandidat.kesepakatan._riwayat-versi')</div>
        </div>
    </div>
@endsection
