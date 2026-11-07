{{--
    Kartu Pengalaman Kerja pada tab Keterampilan & Pengalaman.
--}}
@php
    $totalMonths = 0;
    foreach ($experiences as $exp) {
        $dur = (string) $exp->duration;
        if (preg_match('/(\d+)\s*(?:tahun|thn)/i', $dur, $m)) {
            $totalMonths += ((int) $m[1]) * 12;
        }
        if (preg_match('/(\d+)\s*(?:bulan|bln)/i', $dur, $m)) {
            $totalMonths += (int) $m[1];
        }
    }

    if ($totalMonths > 0) {
        $years = intdiv($totalMonths, 12);
        $months = $totalMonths % 12;
        $parts = [];
        if ($years > 0) {
            $parts[] = $years . ' thn';
        }
        if ($months > 0) {
            $parts[] = $months . ' bln';
        }
        $totalDurationLabel = 'Total ' . implode(' ', $parts);
    } else {
        $totalDurationLabel = $experiences->count() . ' pengalaman';
    }
@endphp

<section class="card" id="bagian-pengalaman">
    <div class="ch">
        <i data-lucide="briefcase" class="ic" width="20" height="20"></i>
        <h3>Pengalaman Kerja</h3>
        <div class="r">
            <span class="sub">{{ $totalDurationLabel }}</span>
        </div>
    </div>

    <div>
        @forelse ($experiences as $exp)
            <div class="exp-item">
                <div class="av">
                    <i data-lucide="briefcase" class="ic" width="17" height="17"></i>
                </div>
                <div class="exp-body">
                    <div class="b">{{ $exp->work_type }}</div>
                    <div style="margin-top:5px">
                        <span class="bd grey">
                            <i data-lucide="clock" class="ic" width="12" height="12"></i>{{ $exp->duration }}
                        </span>
                    </div>
                    @if ($exp->description)
                        <p class="exp-desc">{{ $exp->description }}</p>
                    @endif
                </div>
                <div class="exp-act">
                    <button type="button" class="ic-btn" title="Ubah pengalaman">
                        <i data-lucide="pencil" class="ic" width="15" height="15"></i>
                    </button>
                    <button type="button" class="ic-btn" title="Hapus pengalaman">
                        <i data-lucide="trash-2" class="ic" width="15" height="15"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="sub" style="padding:4px 0 12px">Belum ada pengalaman kerja yang ditambahkan.</div>
        @endforelse
    </div>

    {{-- Form tambah pengalaman kerja ditempatkan di sini. --}}
    <div class="exp-form-box">
        <div class="b" style="font-size:13.5px;margin-bottom:14px">Tambah pengalaman</div>

        <div class="form c2">
            <div class="fl">
                <label for="work_type">Bidang / jenis pekerjaan <i>*</i></label>
                <div class="in">
                    <input type="text" id="work_type" name="work_type" placeholder="Contoh: Admin media sosial toko online">
                </div>
            </div>

            <div class="fl">
                <label for="duration">Durasi <i>*</i></label>
                <div class="in">
                    <input type="text" id="duration" name="duration" placeholder="Contoh: 3 bulan">
                </div>
            </div>

            <div class="fl span2">
                <label for="exp_description">Deskripsi pekerjaan</label>
                <div class="in ta">
                    <textarea id="exp_description" name="description" rows="3" placeholder="Jelaskan tugas dan hasil yang Anda kerjakan"></textarea>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:16px">
            <button type="button" class="btn p">
                <i data-lucide="plus" class="ic" width="16" height="16"></i>Simpan Pengalaman
            </button>
        </div>
    </div>
</section>
