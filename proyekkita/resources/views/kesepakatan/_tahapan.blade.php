{{--
    Linimasa 5 tahap (Figma frame 24/25), dipakai halaman kandidat dan UMKM. Variabel: $stages (SubmissionService::stages).
--}}
@php
    $stageIcon = ['done' => 'circle-check', 'current' => 'circle-dot', 'todo' => 'circle'];
@endphp

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
