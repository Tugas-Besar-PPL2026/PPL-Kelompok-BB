{{--
    [PBI-25 / FR-12] Tawaran Kesepakatan Pekerjaan (Kandidat), mockup 4.5.23.
    Data dari AgreementResponseController@show. Hanya untuk kesepakatan berstatus Dikirim.

    Variabel: $agreement, $project, $umkm, $code, $terms, $deliverables, $deadline
    Form mengirim `tanggapan` (setujui|tolak), `alasan`, dan `pemahaman` ke route kandidat.kesepakatan.tanggapi.store.
    Tidak ada pilihan yang dipilih otomatis, sehingga kandidat harus memilih sendiri.
--}}
@extends('layouts.app')
@section('title', 'Tawaran Kesepakatan')

@use('App\Support\SelectionPresenter')
@include('kesepakatan._gaya')

@php
    $choice = old('tanggapan');
    $rating = $umkm?->averageRating();
@endphp

@section('content')
    <div class="crumb">
        <a href="{{ route('kandidat.kesepakatan.index', ['status' => 'sent']) }}">Kesepakatan &amp; Progres</a>
        <i data-lucide="chevron-right" class="ic" width="14" height="14"></i>
        <span>{{ $code }}</span>
    </div>
    <div class="ph">
        <div>
            <h1>Tawaran Kesepakatan Pekerjaan</h1>
            <p>Tinjau isi kesepakatan sebelum memulai pekerjaan.</p>
        </div>
    </div>

    <div class="grid g21 gtop" dusk="halaman-tanggapi">
        {{-- Isi kesepakatan (hanya baca) --}}
        <section class="card" dusk="isi-tawaran">
            <div class="ks-head">
                <div class="tags">
                    <span class="bd warn" dusk="status-kesepakatan"><i data-lucide="clock" class="ic" width="13" height="13"></i>Menunggu Persetujuan Anda</span>
                    <span class="sub" dusk="kode-kesepakatan">{{ $code }} · dikirim {{ SelectionPresenter::dateTime($agreement->sent_at) }}</span>
                </div>
            </div>
            <h2 class="ks-title" dusk="judul-proyek">{{ $project->title }}</h2>
            <div class="sub">{{ collect([$umkm?->business_name, $umkm?->city])->filter()->join(' · ') }}</div>

            <div class="hr"></div>

            <div class="ks-h" style="margin-top:0">Ruang lingkup pekerjaan</div>
            <div class="ks-text" dusk="ruang-lingkup">{{ $agreement->scope }}</div>

            <div class="ks-h">Hasil yang harus diserahkan</div>
            <ul class="ks-list" dusk="hasil-diserahkan">
                @foreach ($deliverables as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>

            <div class="ks-facts">
                <div class="ks-fact"><div class="l">Tenggat penyelesaian</div><div class="v" dusk="tenggat">{{ $deadline['date'] }}</div></div>
                <div class="ks-fact"><div class="l">Total kompensasi</div><div class="v num" dusk="total-kompensasi">{{ $terms['total'] }}</div></div>
                <div class="ks-fact"><div class="l">Batas revisi</div><div class="v" dusk="batas-revisi">{{ $agreement->revision_limit }} kali</div></div>
            </div>

            <div class="ks-h">Tahapan pembayaran</div>
            @include('kesepakatan._termin', ['terms' => $terms])

            <div class="al info mt16">
                <i data-lucide="info" class="ic" width="18" height="18"></i>
                <div>Pembayaran dilakukan langsung oleh UMKM di luar aplikasi. ProyekKita mencatat setiap termin dan Anda mengonfirmasi penerimaannya.</div>
            </div>
        </section>

        <div class="ks-stack">
            {{-- Tanggapan kandidat --}}
            <section class="card" dusk="panel-tanggapan">
                <div class="ch"><i data-lucide="square-pen" class="ic" width="20" height="20"></i><h3>Tanggapan Anda</h3></div>

                <form method="POST" action="{{ route('kandidat.kesepakatan.tanggapi.store', $agreement) }}" class="form" novalidate dusk="form-tanggapan">
                    @csrf

                    <div class="opts" role="radiogroup" aria-label="Tanggapan kesepakatan">
                        <label class="opt" dusk="opsi-setujui">
                            <input type="radio" name="tanggapan" value="setujui" @checked($choice === 'setujui')>
                            <span class="rd"></span>
                            <span><b>Setujui kesepakatan</b><span class="d">Kesepakatan langsung berstatus aktif dan pekerjaan dapat dimulai.</span></span>
                        </label>
                        <label class="opt" dusk="opsi-tolak">
                            <input type="radio" name="tanggapan" value="tolak" @checked($choice === 'tolak')>
                            <span class="rd"></span>
                            <span><b>Tolak kesepakatan</b><span class="d">Wajib menyertakan alasan penolakan.</span></span>
                        </label>
                    </div>
                    @error('tanggapan') <div class="hint err" dusk="galat-tanggapan">{{ $message }}</div> @enderror

                    <div class="fl">
                        <label for="alasan">Alasan penolakan</label>
                        <div class="in ta @error('alasan') err @enderror">
                            <textarea id="alasan" name="alasan" rows="3" maxlength="1000" dusk="alasan-penolakan" placeholder="Isi alasan jika menolak kesepakatan">{{ old('alasan') }}</textarea>
                        </div>
                        @if ($errors->has('alasan'))
                            <div class="hint err" dusk="galat-alasan">{{ $errors->first('alasan') }}</div>
                        @else
                            <div class="hint">Hanya wajib diisi saat menolak.</div>
                        @endif
                    </div>

                    <div>
                        <label class="pernyataan">
                            <input type="checkbox" name="pemahaman" value="1" dusk="pemahaman" @checked(old('pemahaman'))>
                            <span>Saya telah membaca dan memahami ruang lingkup, tenggat, batas revisi, dan tahapan pembayaran.</span>
                        </label>
                        @error('pemahaman') <div class="hint err mt8" dusk="galat-pemahaman">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <button type="submit" class="btn p" dusk="kirim-tanggapan">
                            <i data-lucide="send" class="ic" width="16" height="16"></i>Kirim Tanggapan
                        </button>
                    </div>
                </form>
            </section>

            {{-- Pihak UMKM --}}
            <section class="card" dusk="pihak-umkm">
                <div class="ch"><i data-lucide="store" class="ic" width="20" height="20"></i><h3>Pihak UMKM</h3></div>
                <div class="row">
                    <div class="av">{{ SelectionPresenter::initials($umkm?->business_name) }}</div>
                    <div>
                        <div class="b">{{ $umkm?->business_name }}</div>
                        @if ($umkm?->verification_status)
                            <span class="bd {{ $umkm->verification_status->badge() }}">{{ $umkm->verification_status->label() }}</span>
                        @endif
                    </div>
                </div>
                <div class="sub mt12">
                    @if ($rating !== null)
                        <i data-lucide="star" class="ic star" width="14" height="14"></i> {{ SelectionPresenter::decimal($rating) }} rata-rata penilaian
                    @else
                        Belum ada penilaian
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
