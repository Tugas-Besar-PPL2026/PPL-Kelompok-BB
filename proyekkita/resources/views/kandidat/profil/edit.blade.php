@extends('layouts.app')
@section('title', 'Profil & Portofolio')

@push('styles')
<style>
    .tag.on {
        background: var(--pri2);
        color: #fff;
        border-color: var(--pri2);
        padding: 5px 11px;
        gap: 7px;
    }
    .tag-form {
        display: inline-flex;
    }
    .tag-rm {
        background: none;
        border: 0;
        color: rgba(255, 255, 255, .85);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
    }
    .tag-rm:hover {
        color: #fff;
    }
    .skill-box {
        border: 1px solid var(--line);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        max-height: 220px;
        overflow-y: auto;
    }
    .skill-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 14px;
        border-bottom: 1px solid var(--line2);
        font-size: 13.5px;
        color: var(--ink);
    }
    .skill-row:last-child {
        border-bottom: none;
    }
    .skill-picked {
        font-size: 12.5px;
        color: #9A9EAD;
        font-weight: 500;
    }
    .skill-add {
        background: none;
        border: 0;
        font-size: 12.5px;
        font-weight: 500;
        color: var(--mut);
        padding: 2px 6px;
        border-radius: 4px;
    }
    .skill-add:hover {
        color: var(--pri2);
        background: var(--panel);
    }
    .exp-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 0;
        border-bottom: 1px solid var(--line2);
    }
    .exp-item:first-child {
        padding-top: 2px;
    }
    .exp-body {
        flex: 1;
        min-width: 0;
    }
    .exp-desc {
        font-size: 13px;
        color: var(--mut);
        margin-top: 7px;
        line-height: 1.5;
    }
    .exp-act {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: none;
    }
    .ic-btn {
        background: none;
        border: 0;
        color: var(--mut);
        padding: 4px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .ic-btn:hover {
        color: var(--ink);
        background: var(--panel);
    }
    .exp-form-box {
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 18px 20px;
        margin-top: 16px;
        background: #fff;
    }
</style>
@endpush

@section('content')
    <div class="ph">
        <div>
            <h1>Profil &amp; Portofolio</h1>
            <p>Lengkapi profil agar UMKM dapat menilai kesesuaian Anda saat menyeleksi lamaran.</p>
        </div>
    </div>

    <div class="tabs">
        <a href="{{ Route::has('kandidat.profil.datadiri') ? route('kandidat.profil.datadiri') : '#' }}">Data Diri</a>
        <a href="{{ route('kandidat.profil.edit') }}" class="on">Keterampilan &amp; Pengalaman</a>
        <a href="{{ Route::has('kandidat.profil.portofolio') ? route('kandidat.profil.portofolio') : '#' }}">Portofolio</a>
    </div>

    <div class="grid g21">
        <div>
            @include('kandidat.profil._keterampilan')
            @include('kandidat.profil._pengalaman')
        </div>
        <div>
            @include('kandidat.profil._samping')
        </div>
    </div>
@endsection
