@extends('layouts.app')
@section('title', 'Profil Usaha')

@push('styles')
<style>
    .business-combo {
        position: relative;
    }

    .business-combo .in {
        display: flex;
        align-items: center;
    }

    .business-combo .in input {
        flex: 1;
        min-width: 0;
    }

    .business-combo-toggle {
        border: none;
        background: transparent;
        cursor: pointer;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .business-combo-options {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        z-index: 100;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,.12);
        max-height: 220px;
        overflow-y: auto;
    }

    .business-combo-option {
        display: block;
        width: 100%;
        padding: 11px 14px;
        border: none;
        background: white;
        text-align: left;
        cursor: pointer;
        font: inherit;
    }

    .business-combo-option:hover {
        background: #f3f4f6;
    }

    .business-combo-options[hidden] {
        display: none;
    }
</style>
@endpush


@section('content')
    @php $status = $profile?->verification_status; @endphp

    <div class="ph">
        <div>
            <h1>Profil Usaha</h1>
            <p>Kelola identitas dan legalitas usaha yang menjadi bahan verifikasi admin dan informasi bagi kandidat.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('umkm.profil.update') }}" novalidate>
        @csrf
        @method('PUT')

        <div class="grid g21">
            <div>
                <section class="card">
                    <div class="ch"><i data-lucide="store" class="ic" width="20" height="20"></i><h3>Informasi Usaha</h3></div>
                    <div class="form c2">
                        <div class="fl">
                            <label for="business_name">Nama usaha <i>*</i></label>
                            <div class="in @error('business_name') err @enderror">
                                <input id="business_name" name="business_name" dusk="nama-usaha" maxlength="150" value="{{ old('business_name', $profile?->business_name) }}">
                            </div>
                            @error('business_name') <div class="hint err" dusk="galat-nama-usaha">{{ $message }}</div> @enderror
                        </div>


                        <div class="fl">
                            <label for="business_field">Bidang usaha <i>*</i></label>

                            <div class="business-combo">
                                <div class="in @error('business_field') err @enderror">
                                    <input
                                        id="business_field"
                                        name="business_field"
                                        type="text"
                                        dusk="bidang-usaha"
                                        maxlength="100"
                                        placeholder="Pilih atau ketik bidang usaha"
                                        value="{{ old('business_field', $profile?->business_field) }}"
                                        autocomplete="off"
                                        aria-expanded="false"
                                        aria-controls="business-field-options"
                                    >

                                    <button
                                        type="button"
                                        class="business-combo-toggle"
                                        id="business-field-toggle"
                                        aria-label="Tampilkan pilihan bidang usaha"
                                    >
                                        <i data-lucide="chevron-down" width="18" height="18"></i>
                                    </button>
                                </div>

                                <div
                                    class="business-combo-options"
                                    id="business-field-options"
                                    hidden
                                >
                                    @foreach ([
                                        'Kuliner & Minuman',
                                        'Fashion',
                                        'Kerajinan',
                                        'Jasa Laundry',
                                        'Pertanian',
                                        'Jasa'
                                    ] as $opt)
                                        <button
                                            type="button"
                                            class="business-combo-option"
                                            data-value="{{ $opt }}"
                                        >
                                            {{ $opt }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="hint">
                                Pilih bidang usaha yang tersedia atau ketik bidang usaha sendiri.
                            </div>

                            @error('business_field')
                                <div class="hint err" dusk="galat-bidang-usaha">{{ $message }}</div>
                            @enderror
                        </div>


                        <div class="fl span2">
                            <label for="description">Deskripsi usaha <i>*</i></label>
                            <div class="in ta @error('description') err @enderror">
                                <textarea id="description" name="description" dusk="deskripsi-usaha" rows="3">{{ old('description', $profile?->description) }}</textarea>
                            </div>
                            @error('description') <div class="hint err" dusk="galat-deskripsi-usaha">{{ $message }}</div> @enderror
                        </div>

                        <div class="fl">
                            <label for="contact_phone">Nomor kontak <i>*</i></label>
                            <div class="in @error('contact_phone') err @enderror">
                                <input id="contact_phone" name="contact_phone" dusk="kontak-usaha" maxlength="20" value="{{ old('contact_phone', $profile?->contact_phone) }}">
                            </div>
                            @error('contact_phone') <div class="hint err" dusk="galat-kontak-usaha">{{ $message }}</div> @enderror
                        </div>

                        <div class="fl">
                            <label for="city">Kota <i>*</i></label>
                            <div class="in @error('city') err @enderror">
                                <input id="city" name="city" dusk="kota-usaha" maxlength="100" value="{{ old('city', $profile?->city) }}">
                            </div>
                            @error('city') <div class="hint err" dusk="galat-kota-usaha">{{ $message }}</div> @enderror
                        </div>

                        <div class="fl span2">
                            <label for="address">Alamat <i>*</i></label>
                            <div class="in @error('address') err @enderror">
                                <input id="address" name="address" dusk="alamat-usaha" maxlength="500" value="{{ old('address', $profile?->address) }}">
                            </div>
                            @error('address') <div class="hint err" dusk="galat-alamat-usaha">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="ch"><i data-lucide="file-check" class="ic" width="20" height="20"></i><h3>Informasi Legalitas Usaha</h3></div>
                    <div class="form c2">
                        <div class="fl">
                            <label for="nib">Nomor Induk Berusaha (NIB)</label>
                            <div class="in @error('nib') err @enderror">
                                <input id="nib" name="nib" dusk="nib" inputmode="numeric" maxlength="20" value="{{ old('nib', $profile?->nib) }}">
                            </div>
                            @error('nib')
                                <div class="hint err" dusk="galat-nib">{{ $message }}</div>
                            @else
                                <div class="hint">13 digit NIB dari OSS. Wajib bila tidak ada tautan usaha.</div>
                            @enderror
                        </div>

                        <div class="fl">
                            <label for="business_link">Tautan informasi usaha</label>
                            <div class="in @error('business_link') err @enderror">
                                <input id="business_link" name="business_link" dusk="tautan-usaha" maxlength="255" value="{{ old('business_link', $profile?->business_link) }}">
                            </div>
                            @error('business_link')
                                <div class="hint err" dusk="galat-tautan">{{ $message }}</div>
                            @else
                                <div class="hint">Instagram, marketplace, atau situs usaha.</div>
                            @enderror
                        </div>
                    </div>

                    <div class="al warn mt16">
                        <i data-lucide="triangle-alert" class="ic" width="19" height="19"></i>
                        <div>
                            <b>Perubahan pada legalitas memerlukan verifikasi ulang</b>
                            Mengubah nama usaha, NIB, atau tautan informasi usaha akan mengembalikan status verifikasi menjadi Menunggu Pemeriksaan.
                        </div>
                    </div>
                </section>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px">
                    <a href="{{ route('umkm.profil.edit') }}" class="btn o">Batal</a>
                    <button type="submit" class="btn p" dusk="simpan-profil">Simpan Perubahan</button>
                </div>
            </div>

            <div>
                <section class="card">
                    <div class="ch"><i data-lucide="shield-check" class="ic" width="20" height="20"></i><h3>Status Verifikasi</h3></div>
                    @if ($profile)
                        <span class="bd {{ $status->badge() }}" dusk="status-verifikasi">{{ $status->label() }}</span>
                        @if ($status->value === 'verified')
                            <p class="sub mt12">
                                @if ($profile->verified_at)
                                    Terverifikasi sejak {{ $profile->verified_at->translatedFormat('d F Y') }}.
                                @endif
                                Anda dapat mengajukan proyek untuk dipublikasikan.
                            </p>
                        @elseif ($status->value === 'rejected')
                            <div class="al bad mt12" dusk="alasan-penolakan-umkm">
                                <i data-lucide="circle-alert" class="ic" width="19" height="19"></i>
                                <div><b>Alasan penolakan</b>{{ $profile->rejection_reason }}</div>
                            </div>
                            <p class="sub mt12">Perbaiki data lalu simpan untuk mengajukan pemeriksaan ulang.</p>
                        @else
                            <p class="sub mt12">Admin sedang memeriksa data usaha Anda. Anda belum dapat mengajukan proyek untuk dipublikasikan.</p>
                        @endif
                    @else
                        <span class="bd grey" dusk="status-verifikasi">Belum diisi</span>
                        <p class="sub mt12">Lengkapi profil usaha, lalu simpan untuk memulai verifikasi.</p>
                    @endif
                </section>

                <section class="card">
                    <div class="ch"><i data-lucide="star" class="ic" width="20" height="20"></i><h3>Rata-rata Penilaian dari Kandidat</h3></div>
                    @if ($average)
                        <div dusk="rata-rata-rating">
                            <span style="font-size:32px;font-weight:700" dusk="nilai-rating">{{ number_format($average, 1, ',', '') }}</span>
                            <span style="color:#F59E0B">{{ str_repeat('★', (int) round($average)) }}</span>
                            <div class="sub" dusk="jumlah-evaluasi">berdasarkan {{ $ratingCount }} evaluasi</div>
                        </div>
                        @foreach ([5, 4, 3, 2, 1] as $star)
                            @php
                                $n = $distribution[$star] ?? 0;
                                $pct = $ratingCount ? round($n / $ratingCount * 100) : 0;
                            @endphp
                            <div class="row mt8" style="gap:8px">
                                <span class="sub" style="width:24px">{{ $star }}★</span>
                                <div class="prog" style="flex:1"><i style="width:{{ $pct }}%;background:#F59E0B"></i></div>
                                <span class="sub" style="width:20px;text-align:right" dusk="jumlah-bintang-{{ $star }}">{{ $n }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="sub" dusk="rata-rata-rating">Belum ada penilaian.</p>
                    @endif
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('business_field');
    const toggle = document.getElementById('business-field-toggle');
    const dropdown = document.getElementById('business-field-options');
    const options = dropdown.querySelectorAll('.business-combo-option');

    function openDropdown() {
        dropdown.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown() {
        dropdown.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    }

    function filterOptions() {
        const keyword = input.value.toLowerCase().trim();
        let found = false;

        options.forEach(function (option) {
            const match = option.dataset.value.toLowerCase()
                .includes(keyword);

            option.hidden = !match;
            if (match) found = true;
        });

        if (found) {
            openDropdown();
        } else {
            closeDropdown();
        }
    }

    input.addEventListener('focus', function () {
        options.forEach(option => option.hidden = false);
        openDropdown();
    });

    input.addEventListener('input', filterOptions);

    toggle.addEventListener('click', function () {
        if (dropdown.hidden) {
            options.forEach(option => option.hidden = false);
            openDropdown();
            input.focus();
        } else {
            closeDropdown();
        }
    });

    options.forEach(function (option) {
        option.addEventListener('click', function () {
            input.value = option.dataset.value;
            closeDropdown();
        });
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDropdown();
        }
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.business-combo')) {
            closeDropdown();
        }
    });
});
</script>
@endpush
