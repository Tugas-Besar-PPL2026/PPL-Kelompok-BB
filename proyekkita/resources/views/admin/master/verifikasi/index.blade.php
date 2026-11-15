@extends('layouts.app')
@section('title', 'Verifikasi UMKM')

@section('content')
    <div class="ph">
        <div>
            <h1>Verifikasi Akun UMKM</h1>
            <p>Periksa identitas dan data legalitas usaha sebelum UMKM diizinkan mengajukan proyek.</p>
        </div>
    </div>

    <div class="grid g12">
        {{-- Antrean --}}
        <section class="card">
            <div class="tabs">
                @foreach (\App\Enums\VerificationStatus::cases() as $s)
                    <a href="{{ route('admin.verifikasi.index', ['status' => $s->value]) }}"
                       dusk="antrean-{{ $s->value }}"
                       class="{{ $status === $s ? 'on' : '' }}">
                        {{ $s->label() }} <span>{{ $counts[$s->value] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            @forelse ($queue as $item)
                <a href="{{ route('admin.verifikasi.index', ['status' => $status->value, 'usaha' => $item->id]) }}"
                   dusk="item-usaha-{{ $item->id }}"
                   style="display:block;padding:12px 0;border-bottom:1px solid var(--line2)">
                    <b>{{ $item->business_name }}</b>
                    <div class="sub">{{ $item->business_field }} · {{ $item->city }}</div>
                </a>
            @empty
                <p class="sub" dusk="antrean-kosong">Tidak ada usaha pada antrean ini.</p>
            @endforelse
        </section>

        {{-- Detail + keputusan --}}
        <div>
            @if ($selected)
                <section class="card" dusk="detail-usaha">
                    <div class="ch">
                        <i data-lucide="store" class="ic" width="20" height="20"></i>
                        <h3>{{ $selected->business_name }}</h3>
                        <div class="r"><span class="bd {{ $selected->verification_status->badge() }}">{{ $selected->verification_status->label() }}</span></div>
                    </div>

                    <div class="form c2">
                        <div class="fl"><label>Pemilik akun</label><div>{{ $selected->user?->name }}</div></div>
                        <div class="fl"><label>Bidang usaha</label><div>{{ $selected->business_field }}</div></div>
                        <div class="fl span2"><label>Deskripsi</label><div>{{ $selected->description }}</div></div>
                        <div class="fl"><label>Kontak</label><div>{{ $selected->contact_phone }}</div></div>
                        <div class="fl"><label>Kota</label><div>{{ $selected->city }}</div></div>
                        <div class="fl span2"><label>Alamat</label><div>{{ $selected->address }}</div></div>
                        <div class="fl"><label>NIB</label><div dusk="detail-nib">{{ $selected->nib ?: '—' }}</div></div>
                        <div class="fl"><label>Tautan informasi usaha</label><div>{{ $selected->business_link ?: '—' }}</div></div>
                    </div>

                    @if ($selected->verification_status->value === 'rejected')
                        <div class="al bad" style="margin-top:14px">
                            <i data-lucide="circle-alert" class="ic" width="19" height="19"></i>
                            <div><b>Alasan penolakan</b>{{ $selected->rejection_reason }}</div>
                        </div>
                    @endif
                </section>

                @if ($selected->verification_status->value === 'pending')
                    <section class="card">
                        <div class="ch"><i data-lucide="gavel" class="ic" width="20" height="20"></i><h3>Keputusan Verifikasi</h3></div>

                        <form method="POST" action="{{ route('admin.verifikasi.decide', $selected->id) }}" novalidate>
                            @csrf
                            <div class="fl">
                                <label for="reason">Alasan penolakan <span class="sub">(wajib diisi bila menolak)</span></label>
                                <div class="in ta @error('reason') err @enderror">
                                    <textarea id="reason" name="reason" dusk="alasan-penolakan" rows="3" placeholder="Contoh: NIB tidak sesuai dengan nama usaha.">{{ old('reason') }}</textarea>
                                </div>
                                @error('reason') <div class="hint err" dusk="galat-alasan">{{ $message }}</div> @enderror
                            </div>

                            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
                                <button type="submit" name="decision" value="reject" class="btn r" dusk="btn-tolak">Tolak</button>
                                <button type="submit" name="decision" value="approve" class="btn g" dusk="btn-setujui">Setujui</button>
                            </div>
                        </form>
                    </section>
                @endif
            @else
                <section class="card"><p class="sub">Pilih usaha dari antrean untuk melihat detail.</p></section>
            @endif
        </div>
    </div>
@endsection