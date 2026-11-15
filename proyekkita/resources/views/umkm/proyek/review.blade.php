@extends('layouts.app')
@section('title', 'Tinjau & Ajukan Proyek')

@php
    $total = (float) $project->total_compensation;
    $termTotal = (float) $project->paymentTerms->sum('amount');
    $rupiah = fn ($value) => 'Rp' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? $value->locale('id')->translatedFormat('d M Y') : '-';
    $porsi = fn ($value) => $total > 0 ? round($value / $total * 100) . '%' : '-';
    $kode = 'PRJ-' . ($project->created_at?->format('Y') ?? now()->format('Y')) . '-' . str_pad($project->id, 3, '0', STR_PAD_LEFT);
    $hasil = collect(preg_split('/\r\n|\r|\n/', (string) $project->expected_output))
        ->map(fn ($line) => trim(ltrim(trim($line), "•-* \t")))
        ->filter()
        ->values();
    $allOk = collect($checks)->every(fn ($check) => $check['ok']);
@endphp

@section('content')
    <div class="ph">
        <div>
            <div class="crumb">
                <a href="{{ Route::has('umkm.proyek.index') ? route('umkm.proyek.index') : '#' }}">Proyek Saya</a>
                <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
                @if ($canSubmit)
                    <a href="{{ route('umkm.proyek.edit', $project) }}">Ubah Draf</a>
                    <i data-lucide="chevron-right" class="ic" width="13" height="13"></i>
                @endif
                <span>Tinjau &amp; Ajukan</span>
            </div>
            <h1>Tinjau &amp; Ajukan Proyek</h1>
            <p>Periksa kembali pratinjau proyek sebelum diajukan untuk diperiksa admin.</p>
        </div>
        @if ($canSubmit)
            <div class="act">
                <a href="{{ route('umkm.proyek.edit', $project) }}" class="btn o" dusk="kembali-ubah">
                    <i data-lucide="pencil" class="ic" width="16" height="16"></i>Kembali Ubah Draf
                </a>
            </div>
        @endif
    </div>

    @error('ajukan')
        <div class="al bad" style="margin-bottom:16px" dusk="error-ajukan">
            <i data-lucide="circle-alert" class="ic" width="18" height="18"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <div class="grid g31">
        {{-- Pratinjau proyek seperti yang nanti dilihat admin dan kandidat --}}
        <section class="card">
            <div class="row" style="gap:10px;flex-wrap:wrap">
                <span class="bd {{ $project->status->badge() }}" dusk="status-proyek">{{ $project->status->label() }}</span>
                <span class="sub">{{ $kode }} · {{ $project->category?->name ?? 'Tanpa kategori' }}</span>
            </div>

            <h2 class="rv-title">{{ $project->title }}</h2>
            <p class="rv-desc">{{ $project->description }}</p>

            <h4 class="rv-h">Hasil pekerjaan yang diharapkan</h4>
            @if ($hasil->count() > 1)
                <ul class="rv-list">
                    @foreach ($hasil as $baris)
                        <li>{{ $baris }}</li>
                    @endforeach
                </ul>
            @else
                <p class="rv-desc">{{ $hasil->first() ?? '-' }}</p>
            @endif

            <h4 class="rv-h">Keterampilan</h4>
            <div class="tags">
                @forelse ($project->skills as $skill)
                    <span class="tag">{{ $skill->name }}</span>
                @empty
                    <span class="sub">Belum ada keterampilan dipilih.</span>
                @endforelse
            </div>

            <div class="grid g3 rv-stats">
                <div class="stat"><div class="l">Tenggat lamaran</div><div class="v">{{ $tanggal($project->application_deadline) }}</div></div>
                <div class="stat"><div class="l">Target penyelesaian</div><div class="v">{{ $tanggal($project->target_completion_date) }}</div></div>
                <div class="stat"><div class="l">Kuota kandidat</div><div class="v">{{ $project->quota }} orang</div></div>
            </div>

            <div class="between rv-pay">
                <h4 class="rv-h" style="margin:0">Skema pembayaran per kandidat</h4>
                <span class="rv-total num">{{ $rupiah($total) }}</span>
            </div>

            <table class="t">
                <thead>
                    <tr><th>Termin</th><th>Kondisi / Target Pembayaran</th><th>Nominal</th><th>Porsi</th></tr>
                </thead>
                <tbody>
                    @forelse ($project->paymentTerms as $term)
                        <tr>
                            <td class="b nw">Termin {{ $term->term_number }}</td>
                            <td>{{ $term->condition }}</td>
                            <td class="b num nw">{{ $rupiah($term->amount) }}</td>
                            <td class="mut num">{{ $porsi((float) $term->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="mut">Belum ada termin pembayaran.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td class="b">Total</td>
                        <td></td>
                        <td class="b num nw">{{ $rupiah($termTotal) }}</td>
                        <td class="mut num">{{ $porsi($termTotal) }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>

        {{-- Syarat pengajuan dan tombol ajukan --}}
        <div>
            <section class="card">
                <div class="ch"><i data-lucide="clipboard-check" class="ic" width="20" height="20"></i><h3>Syarat Pengajuan</h3></div>

                @foreach ($checks as $key => $check)
                    <div class="syarat {{ $check['ok'] ? 'ok' : 'no' }}" dusk="syarat-{{ $key }}">
                        <i data-lucide="{{ $check['ok'] ? 'circle-check' : 'circle-x' }}" class="ic" width="20" height="20"></i>
                        <div>
                            <div class="b">{{ $check['label'] }}</div>
                            <div class="sub">{{ $check['note'] }}</div>
                        </div>
                    </div>
                @endforeach

                <div class="hr"></div>

                @if ($canSubmit)
                    <form method="POST" action="{{ route('umkm.proyek.submit', $project) }}" id="form-ajukan">
                        @csrf
                        <label class="pernyataan">
                            <input type="checkbox" name="pernyataan" value="1" id="pernyataan" dusk="pernyataan" @checked(old('pernyataan')) @disabled(! $allOk)>
                            <span>Saya menyatakan informasi proyek benar dan sesuai dengan kebutuhan usaha.</span>
                        </label>
                        @error('pernyataan')
                            <div class="hint err">{{ $message }}</div>
                        @enderror

                        <button type="submit" class="btn p" id="tombol-ajukan" dusk="ajukan-moderasi" style="margin-top:14px" @disabled(! $allOk)>
                            <i data-lucide="send" class="ic" width="16" height="16"></i>Ajukan untuk Moderasi
                        </button>
                    </form>

                    @unless ($allOk)
                        <div class="sub" style="margin-top:10px">Lengkapi syarat yang belum terpenuhi melalui halaman Ubah Draf sebelum mengajukan.</div>
                    @endunless

                    <div class="al info" style="margin-top:16px">
                        <i data-lucide="info" class="ic" width="18" height="18"></i>
                        <div>Setelah diajukan, status proyek menjadi <strong>Menunggu Moderasi</strong>. Anda akan menerima notifikasi saat admin menyetujui, meminta perbaikan, atau menolak proyek.</div>
                    </div>
                @else
                    <div class="al info" dusk="info-sudah-diajukan">
                        <i data-lucide="hourglass" class="ic" width="18" height="18"></i>
                        <div>Proyek berstatus <strong>{{ $project->status->label() }}</strong> dan tidak dapat diubah atau diajukan ulang dari halaman ini.</div>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Tombol Ajukan aktif hanya jika semua syarat terpenuhi dan pernyataan dicentang.
    (function () {
        const cek = document.getElementById('pernyataan');
        const tombol = document.getElementById('tombol-ajukan');
        if (!cek || !tombol || cek.disabled) return;
        const sinkron = () => { tombol.disabled = !cek.checked; };
        cek.addEventListener('change', sinkron);
        sinkron();
    })();
</script>
@endpush
