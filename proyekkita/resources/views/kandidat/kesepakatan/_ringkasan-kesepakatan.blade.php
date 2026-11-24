{{--
    [PBI-31] Ringkasan kesepakatan dan linimasa tahap pengerjaan.
    Variabel: $agreement, $project, $code, $umkmName, $candidateName, $deadline, $deadlinePassed, $stages, $form.
--}}
@php
    $latest = $form['latest'];
@endphp

<section class="card" dusk="ringkasan-kesepakatan">
    <div class="ps-head">
        <span class="ps-code">{{ $code }}</span>
        <span class="bd {{ $agreement->status->badge() }}" dusk="status-kesepakatan">{{ $agreement->status->label() }}</span>
        @if ($latest)
            <span class="bd {{ $latest->status->badge() }}" dusk="status-penyerahan-terakhir">{{ $latest->status->label() }}</span>
        @endif
    </div>

    <h2 class="ps-title" dusk="judul-proyek">{{ $project?->title }}</h2>
    <div class="sub">{{ $umkmName }} · bersama {{ $candidateName }}</div>

    @if ($deadline)
        <div class="ps-deadline">
            <span class="sub">Tenggat penyerahan: <b style="color:var(--ink)">{{ $deadline }}</b></span>
            @if ($deadlinePassed)
                <span class="ps-late" dusk="tenggat-lewat">
                    <i data-lucide="clock-alert" class="ic" width="15" height="15"></i>Melewati tenggat {{ $deadline }}
                </span>
            @endif
        </div>
    @endif

    @include('kesepakatan._tahapan')
</section>
