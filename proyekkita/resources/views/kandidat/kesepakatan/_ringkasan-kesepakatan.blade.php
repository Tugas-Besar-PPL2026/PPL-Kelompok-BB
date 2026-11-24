{{--
    [PBI-31] Ringkasan kesepakatan dan linimasa tahap pengerjaan.
    Variabel: $agreement, $project, $code, $umkmName, $candidateName, $deadline, $deadlinePassed, $stages, $form.
--}}
@php
    $latest = $form['latest'];
    $stageIcon = ['done' => 'circle-check', 'current' => 'circle-dot', 'todo' => 'circle'];
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

    <div class="ps-steps" dusk="tahapan">
        @foreach ($stages as $stage)
            <div class="ps-step {{ $stage['state'] }}">
                <div class="bar"></div>
                <div class="lb">
                    <i data-lucide="{{ $stageIcon[$stage['state']] }}" class="ic" width="17" height="17"></i>{{ $stage['label'] }}
                </div>
                <div class="nt">{{ $stage['note'] }}</div>
            </div>
        @endforeach
    </div>
</section>
