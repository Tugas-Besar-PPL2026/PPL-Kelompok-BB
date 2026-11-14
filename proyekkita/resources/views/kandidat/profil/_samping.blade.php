{{--
    Panel kanan halaman Profil & Portofolio kandidat:
    indikator kelengkapan profil dan informasi privasi profil.
--}}
@php
    $hasPersonalData = $profile
        && filled($profile->full_name)
        && filled($profile->headline)
        && filled($profile->university)
        && filled($profile->major)
        && filled($profile->city)
        && filled($profile->summary);

    $hasSkill = $selectedSkills->isNotEmpty();
    $hasExperience = $experiences->isNotEmpty();
    $hasPortfolio = $portfolios->isNotEmpty();
    $hasPhoto = filled($profile?->photo);

    $completeness = ($hasPersonalData ? 25 : 0)
        + ($hasSkill ? 20 : 0)
        + ($hasExperience ? 20 : 0)
        + ($hasPortfolio ? 20 : 0)
        + ($hasPhoto ? 15 : 0);

    $isReadyToApply = $hasPersonalData && $hasSkill && $hasExperience && $hasPortfolio;

    $checks = [
        'datadiri' => ['Data diri & akademik', $hasPersonalData],
        'keterampilan' => ['Minimal 1 keterampilan', $hasSkill],
        'pengalaman' => ['Minimal 1 pengalaman', $hasExperience],
        'portofolio' => ['Minimal 1 portofolio', $hasPortfolio],
        'foto' => ['Foto profil (opsional)', $hasPhoto],
    ];
@endphp

<section class="card">
    <div class="ch">
        <i data-lucide="gauge" class="ic" width="20" height="20"></i>
        <h3>Kelengkapan Profil</h3>
    </div>

    <div class="between" style="margin-bottom:8px">
        <span style="font-size:22px;font-weight:700;letter-spacing:-.3px" id="completeness-pct">{{ $completeness }}%</span>
        <span class="sub" id="completeness-status">{{ $isReadyToApply ? 'siap melamar' : 'belum lengkap' }}</span>
    </div>

    <div class="prog" style="margin-bottom:14px">
        <i id="completeness-bar" style="width:{{ $completeness }}%;background:var(--ok)"></i>
    </div>

    @foreach ($checks as $key => [$label, $checked])
        <div class="row" style="padding:7px 0">
            <span class="chk {{ $checked ? 'on' : '' }}" data-cek="{{ $key }}">
                <i data-lucide="check" class="ic" width="13" height="13"></i>
            </span>
            <span>{{ $label }}</span>
        </div>
    @endforeach
</section>

<section class="card">
    <div class="ch">
        <i data-lucide="eye" class="ic" width="20" height="20"></i>
        <h3>Siapa yang bisa melihat?</h3>
    </div>

    <div class="al info">
        <i data-lucide="info" class="ic" width="19" height="19"></i>
        <div>Profil, keterampilan, pengalaman, dan portofolio Anda hanya dapat dilihat oleh UMKM yang menerima lamaran dari Anda.</div>
    </div>
</section>
