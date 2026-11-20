@extends('layouts.app')
@section('title', 'Profil & Portofolio')

@section('content')
    <div class="ph">
        <div>
            <h1>Profil &amp; Portofolio</h1>
            <p>Lengkapi profil agar UMKM dapat menilai kesesuaian Anda saat menyeleksi lamaran.</p>
        </div>
    </div>

    <div class="tabs">
        <a href="{{ route('kandidat.profil.datadiri') }}" class="on">Data Diri</a>
        <a href="{{ route('kandidat.profil.edit') }}">Keterampilan &amp; Pengalaman</a>
        <a href="{{ Route::has('kandidat.profil.portofolio') ? route('kandidat.profil.portofolio') : '#' }}">Portofolio</a>
    </div>

    <div class="grid g21">
        <div>
            <form method="POST" action="{{ route('kandidat.profil.datadiri.update') }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <section class="card">
                    <div class="ch"><i data-lucide="user-round" class="ic" width="20" height="20"></i><h3>Data Pribadi &amp; Akademik</h3></div>

                    <div style="display:flex;gap:16px;align-items:center;margin-bottom:18px">
                        @if ($profile?->photo)
                            <img src="{{ asset('storage/'.$profile->photo) }}" alt="Foto profil" dusk="foto-tampil" style="width:72px;height:72px;border-radius:12px;object-fit:cover">
                        @else
                            <div class="av" style="width:72px;height:72px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:700">{{ $navInitials }}</div>
                        @endif
                        <div class="fl">
                            <label for="photo">Foto profil <span class="sub">(opsional)</span></label>
                            <input id="photo" type="file" name="photo" dusk="foto-profil" accept="image/png,image/jpeg">
                            <div class="hint">JPG atau PNG, maksimal 2 MB.</div>
                            @error('photo') <div class="hint err" dusk="galat-foto">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="form c2">
                        <div class="fl">
                            <label for="full_name">Nama lengkap <i>*</i></label>
                            <div class="in @error('full_name') err @enderror">
                                <input id="full_name" name="full_name" dusk="nama-lengkap" maxlength="100" value="{{ old('full_name', $prefill['full_name'] ?? $profile?->full_name) }}">
                            </div>
                            @error('full_name') <div class="hint err" dusk="galat-nama-lengkap">{{ $message }}</div> @enderror
                        </div>

                        <div class="fl">
                            <label for="city">Domisili (kota/kabupaten) <i>*</i></label>
                            <div class="in @error('city') err @enderror">
                                <input id="city" name="city" dusk="domisili" maxlength="100" value="{{ old('city', $profile?->city) }}">
                            </div>
                            @error('city') <div class="hint err" dusk="galat-domisili">{{ $message }}</div> @enderror
                        </div>

                        <div class="fl span2">
                            <label for="headline">Headline / deskripsi singkat <i>*</i></label>
                            <div class="in @error('headline') err @enderror">
                                <input id="headline" name="headline" dusk="headline" maxlength="200" value="{{ old('headline', $profile?->headline) }}">
                            </div>
                            @error('headline') <div class="hint err" dusk="galat-headline">{{ $message }}</div> @else <div class="hint">Maksimal 80 karakter. Ditampilkan di bawah nama Anda.</div> @enderror
                        </div>

                        <div class="fl">
                            <label for="university">Universitas <i>*</i></label>
                            <div class="in @error('university') err @enderror">
                                <input id="university" name="university" dusk="universitas" maxlength="150" value="{{ old('university', $profile?->university) }}">
                            </div>
                            @error('university') <div class="hint err" dusk="galat-universitas">{{ $message }}</div> @enderror
                        </div>

                        <div class="fl">
                            <label for="major">Program studi <i>*</i></label>
                            <div class="in @error('major') err @enderror">
                                <input id="major" name="major" dusk="program-studi" maxlength="100" value="{{ old('major', $profile?->major) }}">
                            </div>
                            @error('major') <div class="hint err" dusk="galat-program-studi">{{ $message }}</div> @enderror
                        </div>

                        <div class="fl span2">
                            <label for="summary">Ringkasan diri <i>*</i></label>
                            <div class="in ta @error('summary') err @enderror">
                                <textarea id="summary" name="summary" dusk="ringkasan" rows="4">{{ old('summary', $profile?->summary) }}</textarea>
                            </div>
                            @error('summary') <div class="hint err" dusk="galat-ringkasan">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px">
                        <a href="{{ route('kandidat.profil.datadiri') }}" class="btn o">Batal</a>
                        <button type="submit" class="btn p" dusk="simpan-profil">Simpan Profil</button>
                    </div>
                </section>
            </form>
        </div>

        <div>
            @include('kandidat.profil._samping')
        </div>
    </div>
@endsection
