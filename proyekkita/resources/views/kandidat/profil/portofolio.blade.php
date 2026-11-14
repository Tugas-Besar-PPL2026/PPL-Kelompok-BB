@extends('layouts.app')
@section('title', 'Profil & Portofolio')

@push('styles')
<style>
    .pf-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }
    .pf-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .pf-thumb {
        height: 112px;
        background: var(--pril);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #5263B8;
        flex: none;
    }
    .pf-body {
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 0;
    }
    .pf-title {
        font-weight: 600;
        font-size: 13.5px;
        color: var(--ink);
        line-height: 1.35;
    }
    .pf-desc {
        font-size: 12.5px;
        color: var(--mut);
        margin-top: 5px;
        line-height: 1.45;
    }
    .pf-meta {
        margin-top: auto;
        padding-top: 10px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 12px;
        color: var(--pri);
        word-break: break-word;
    }
    .pf-link {
        display: inline-flex;
        align-items: flex-start;
        gap: 5px;
        color: var(--pri);
        line-height: 1.35;
    }
    .pf-link .ic {
        margin-top: 2px;
        flex: none;
    }
    .pf-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 6px;
        margin-top: 10px;
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
    .pf-dropzone {
        border: 1.5px dashed #C9CCD8;
        border-radius: 10px;
        background: #FAFBFD;
        padding: 22px 16px;
        text-align: center;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        transition: border-color .15s, background .15s;
    }
    .pf-dropzone:hover,
    .pf-dropzone.dragover {
        border-color: var(--pri2);
        background: var(--panel);
    }
    .pf-dropzone.err {
        border-color: var(--bad);
        background: #FFF8F7;
    }
    .pf-drop-title {
        font-weight: 600;
        font-size: 13.5px;
        color: var(--ink);
    }
    .pf-drop-sub {
        font-size: 12px;
        color: var(--mut);
    }
    @media (max-width: 1000px) {
        .pf-cards {
            grid-template-columns: 1fr;
        }
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
        <a href="{{ route('kandidat.profil.edit') }}">Keterampilan &amp; Pengalaman</a>
        <a href="{{ route('kandidat.profil.portofolio') }}" class="on">Portofolio</a>
    </div>

    <div class="grid g21">
        <div>
            @include('kandidat.profil._portofolio')
        </div>
        <div>
            @include('kandidat.profil._samping')
        </div>
    </div>
@endsection
