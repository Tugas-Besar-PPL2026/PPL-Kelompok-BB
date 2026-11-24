{{--
    [PBI-24 / FR-12, FR-11] Susun Kesepakatan Pekerjaan (UMKM), mockup 4.5.22.
    Data dari AgreementComposeController@edit. Halaman ini hanya terbuka selama kesepakatan masih Draf.

    Variabel: $agreement, $application, $project, $candidate, $acceptedAt, $terms, $code
    Tombol "Kirim ke Kandidat" dan "Simpan Draf" berada di panel kanan; keduanya mengirim form yang sama
    (atribut form="form-kesepakatan") dengan nilai `aksi` = kirim | simpan.
--}}
@extends('layouts.app')
@section('title', 'Susun Kesepakatan')

@use('App\Support\SelectionPresenter')
@include('kesepakatan._gaya')

@section('content')
    <div class="crumb">
        <a href="{{ route('umkm.kesepakatan.index', ['status' => 'draft']) }}">Kesepakatan &amp; Progres</a>
        <i data-lucide="chevron-right" class="ic" width="14" height="14"></i>
        <span>Susun Kesepakatan</span>
    </div>
    <div class="ph">
        <div>
            <h1>Susun Kesepakatan Pekerjaan</h1>
            <p>Lengkapi isi kesepakatan sebelum dikirim kepada kandidat yang diterima.</p>
        </div>
    </div>

    <div class="grid g21 gtop" dusk="halaman-susun-kesepakatan">
        <div class="ks-stack">
            {{-- Kandidat yang diterima --}}
            <section class="card ks-cand" dusk="kartu-kandidat">
                <div class="av">{{ SelectionPresenter::initials($candidate->full_name) }}</div>
                <div>
                    <div class="nm" dusk="nama-kandidat">{{ $candidate->full_name }}</div>
                    <div class="sub">{{ collect([$candidate->university, $candidate->major])->filter()->join(' · ') }}</div>
                </div>
                <div class="acc">
                    <span class="bd ok"><i data-lucide="check" class="ic" width="13" height="13"></i>Lamaran Diterima</span>
                    @if ($acceptedAt)
                        <span class="sub">{{ SelectionPresenter::dateTime($acceptedAt) }}</span>
                    @endif
                </div>
            </section>

            {{-- Isi kesepakatan --}}
            <section class="card">
                <div class="ch"><i data-lucide="file-pen-line" class="ic" width="20" height="20"></i><h3>Isi Kesepakatan</h3></div>

                <form id="form-kesepakatan" method="POST" action="{{ route('umkm.kesepakatan.update', $agreement) }}" class="form" dusk="form-kesepakatan" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="fl">
                        <label for="proyek">Proyek</label>
                        <div class="in"><input id="proyek" value="{{ $project->title }}" readonly dusk="nama-proyek"></div>
                    </div>

                    <div class="fl">
                        <label for="scope">Ruang lingkup pekerjaan <i>*</i></label>
                        <div class="in ta @error('scope') err @enderror">
                            <textarea id="scope" name="scope" rows="4" maxlength="5000" dusk="ruang-lingkup" placeholder="Jelaskan pekerjaan yang harus dikerjakan kandidat.">{{ old('scope', $agreement->scope) }}</textarea>
                        </div>
                        @error('scope') <div class="hint err" dusk="galat-ruang-lingkup">{{ $message }}</div> @enderror
                    </div>

                    <div class="fl">
                        <label for="deliverables">Hasil yang harus diserahkan <i>*</i></label>
                        <div class="in ta @error('deliverables') err @enderror">
                            <textarea id="deliverables" name="deliverables" rows="4" maxlength="5000" dusk="hasil-diserahkan" placeholder="Tulis satu hasil per baris, misalnya: Desain label kemasan 3 SKU siap cetak">{{ old('deliverables', $agreement->deliverables) }}</textarea>
                        </div>
                        @error('deliverables') <div class="hint err" dusk="galat-hasil-diserahkan">{{ $message }}</div> @enderror
                        <div class="hint">Tulis satu hasil per baris.</div>
                    </div>

                    <div class="form c2">
                        <div class="fl">
                            <label for="deadline">Tenggat penyelesaian <i>*</i></label>
                            <div class="in @error('deadline') err @enderror">
                                <i data-lucide="calendar-days" class="ic" width="17" height="17"></i>
                                <input type="date" id="deadline" name="deadline" dusk="tenggat" min="{{ now()->toDateString() }}"
                                    value="{{ old('deadline', $agreement->deadline?->format('Y-m-d')) }}">
                            </div>
                            @error('deadline') <div class="hint err" dusk="galat-tenggat">{{ $message }}</div> @enderror
                        </div>

                        <div class="fl">
                            <label for="revision_limit">Batas jumlah revisi <i>*</i></label>
                            <div class="in @error('revision_limit') err @enderror">
                                {{-- type="text" agar nilai seperti "1,5" tetap terkirim dan ditolak oleh validasi server --}}
                                <input type="text" inputmode="numeric" id="revision_limit" name="revision_limit" dusk="batas-revisi"
                                    value="{{ old('revision_limit', $agreement->revision_limit) }}" placeholder="2">
                                <span class="sfx">kali revisi</span>
                            </div>
                            @error('revision_limit') <div class="hint err" dusk="galat-batas-revisi">{{ $message }}</div> @enderror
                        </div>

                        <div class="fl">
                            <label for="total-kompensasi">Total kompensasi</label>
                            <div class="in"><input id="total-kompensasi" value="{{ $terms['total'] }}" readonly dusk="total-kompensasi"></div>
                        </div>

                        <div class="fl">
                            <label for="jumlah-termin">Jumlah termin</label>
                            <div class="in"><input id="jumlah-termin" value="{{ $terms['count'] }} termin" readonly dusk="jumlah-termin"></div>
                        </div>
                    </div>
                    <div class="hint" style="margin-top:-8px">Mengikuti detail proyek yang dipublikasikan.</div>

                    <div>
                        <div class="fl"><label>Tahapan pembayaran (dari detail proyek)</label></div>
                        <div class="mt8">@include('kesepakatan._termin', ['terms' => $terms])</div>
                    </div>
                </form>
            </section>
        </div>

        {{-- Panel kirim kesepakatan --}}
        <div class="ks-stack">
            <section class="card ks-panel" dusk="panel-kirim">
                <div class="ch"><i data-lucide="send" class="ic" width="20" height="20"></i><h3>Kirim Kesepakatan</h3></div>

                <div class="between mb16">
                    <span class="ks-code" dusk="kode-kesepakatan">{{ $code }}</span>
                    <span class="bd {{ $agreement->status->badge() }}" dusk="status-kesepakatan">{{ $agreement->status->label() }}</span>
                </div>

                <div class="tl">
                    <div class="it g">
                        <div class="t1">Draf disusun</div>
                        <div class="t2">Setelah pelamar diterima{{ $acceptedAt ? ' · '.SelectionPresenter::date($acceptedAt) : '' }}</div>
                    </div>
                    <div class="it">
                        <div class="t1">Dikirim ke kandidat</div>
                        <div class="t2">Menunggu tanggapan kandidat</div>
                    </div>
                    <div class="it">
                        <div class="t1">Disetujui kandidat</div>
                        <div class="t2">Kesepakatan berstatus aktif</div>
                    </div>
                </div>

                <div class="al info">
                    <i data-lucide="info" class="ic" width="18" height="18"></i>
                    <div>Kesepakatan hanya aktif setelah disetujui kandidat. Kandidat dapat menolak dengan menyertakan alasan.</div>
                </div>

                <div class="ks-btns">
                    <button type="submit" form="form-kesepakatan" name="aksi" value="kirim" class="btn p" dusk="kirim-kesepakatan">
                        <i data-lucide="send" class="ic" width="16" height="16"></i>Kirim ke Kandidat
                    </button>
                    <button type="submit" form="form-kesepakatan" name="aksi" value="simpan" class="btn o" dusk="simpan-draf">
                        <i data-lucide="save" class="ic" width="16" height="16"></i>Simpan Draf
                    </button>
                </div>
            </section>
        </div>
    </div>
@endsection
