{{--
    [PBI-31] Ditampilkan dengan status 404 saat berkas sebuah versi tidak ada lagi di penyimpanan.
    Variabel: $agreement.
--}}
@extends('layouts.app')
@section('title', '404 · Berkas tidak ditemukan')

@section('content')
    <div class="card" dusk="berkas-tidak-ditemukan">
        <div class="ph" style="margin-bottom:8px">
            <div>
                <h1>404</h1>
                <p>Berkas hasil pekerjaan tidak ditemukan.</p>
            </div>
        </div>
        <a href="{{ route('kandidat.kesepakatan.penyerahan.index', $agreement) }}" class="btn o">
            <i data-lucide="arrow-left" class="ic" width="16" height="16"></i>Kembali ke Penyerahan Hasil
        </a>
    </div>
@endsection
