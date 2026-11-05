@extends('layouts.app')
@section('title', 'Buat Proyek')

@section('content')
    <div class="ph">
        <div>
            <div class="crumb">
                <a href="{{ Route::has('umkm.proyek.index') ? route('umkm.proyek.index') : '#' }}">Proyek Saya</a>
                <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
                <span>Buat Proyek</span>
            </div>
            <h1>Buat Proyek Baru</h1>
            <p>Lengkapi informasi proyek. Draf dapat disimpan dan diajukan untuk moderasi kapan saja.</p>
        </div>
        <div class="act">
            <button type="submit" form="form-proyek" class="btn o">
                <i data-lucide="save" class="ic" width="16" height="16"></i>Simpan sebagai Draf
            </button>
        </div>
    </div>

    <form id="form-proyek" method="POST" action="{{ route('umkm.proyek.store') }}">
        @csrf
        <div class="grid g31">
            <div>@include('umkm.proyek._form')</div>
            <div>@include('umkm.proyek._samping')</div>
        </div>
    </form>
@endsection
