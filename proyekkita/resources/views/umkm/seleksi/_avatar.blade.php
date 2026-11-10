{{--
    Avatar kandidat: foto profil bila ada, selain itu inisial nama.
    Parameter: $profile (CandidateProfile), $size (opsional, mis. 'xl').
    Bila foto gagal dimuat, gambar dibuang dan inisial yang tampil.
--}}
@php
    $initials = \App\Support\SelectionPresenter::initials($profile->full_name);
    $photo = $profile->photo
        ? (preg_match('#^https?://#i', $profile->photo) ? $profile->photo : asset('storage/'.ltrim($profile->photo, '/')))
        : null;
@endphp
<div class="av {{ $size ?? '' }}">{{ $initials }}@if ($photo)<img src="{{ $photo }}" alt="" loading="lazy" onerror="this.remove()">@endif</div>
