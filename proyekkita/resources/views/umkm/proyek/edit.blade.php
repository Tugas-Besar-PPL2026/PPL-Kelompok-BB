@extends('layouts.app')
@section('title', 'Ubah Draf Proyek')

@section('content')
    <div class="ph">
        <div>
            <div class="crumb">
                <a href="{{ Route::has('umkm.proyek.index') ? route('umkm.proyek.index') : '#' }}">Proyek Saya</a>
                <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
                <span>Ubah Draf</span>
            </div>
            <div class="row">
                <h1>{{ $project->title }}</h1>
                <span class="bd {{ $project->status->badge() }}">{{ $project->status->label() }}</span>
            </div>
            <p>Perubahan pada draf belum terlihat oleh kandidat sampai proyek diajukan dan disetujui admin.</p>
        </div>
        <div class="act">
            <button type="submit" form="form-proyek" class="btn o">
                <i data-lucide="save" class="ic" width="16" height="16"></i>Simpan Perubahan
            </button>
        </div>
    </div>

    <form id="form-proyek" method="POST" action="{{ route('umkm.proyek.update', $project) }}">
        @csrf
        @method('PUT')
        <div class="grid g31">
            <div>@include('umkm.proyek._form')</div>
            <div>@include('umkm.proyek._samping')</div>
        </div>
    </form>
@endsection
