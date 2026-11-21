{{--
    [PBI-17] Riwayat perubahan status lamaran dalam bentuk timeline.
    Data yang digunakan: $item, yaitu data lamaran beserta riwayat statusnya.

    Timeline diurutkan dari status terbaru ke status sebelumnya.
    Menggunakan elemen <details> dan <summary> bawaan HTML
    agar riwayat dapat dibuka dan ditutup tanpa JavaScript.

    Bagian ini terbuka otomatis jika $item['timelineOpen'] bernilai true,
    biasanya untuk lamaran aktif yang memiliki lebih dari satu riwayat.

    Jika tidak ada riwayat status, bagian timeline tidak ditampilkan.
--}}
@if (count($item['timeline']) > 0)

    {{-- Menampilkan timeline yang dapat dibuka/tutup.
         Atribut open ditambahkan secara kondisional berdasarkan timelineOpen. --}}
    <details class="la-hist"{{ $item['timelineOpen'] ? ' open' : '' }}>

        {{-- Judul timeline sekaligus tombol untuk membuka/menutup riwayat.
             Menampilkan jumlah entri riwayat status. --}}
        <summary>Riwayat status ({{ count($item['timeline']) }})</summary>

        <div class="tl">

            {{-- Menampilkan setiap entri riwayat status dalam timeline. --}}
            @foreach ($item['timeline'] as $entry)

                {{-- Class dot digunakan untuk membedakan tampilan tiap status
                     pada timeline, misalnya warna atau indikator tertentu. --}}
                <div class="it {{ $entry['dot'] }}">

                    {{-- Nama status yang terjadi pada entri riwayat ini. --}}
                    <div class="t1">{{ $entry['label'] }}</div>

                    {{-- Menampilkan tanggal perubahan status jika tersedia. --}}
                    @if ($entry['date'])
                        <div class="t2">{{ $entry['date'] }}</div>
                    @endif

                    {{-- Menampilkan detail tambahan mengenai perubahan status
                         jika tersedia. --}}
                    @if ($entry['detail'])
                        <div class="t3">{{ $entry['detail'] }}</div>
                    @endif

                    {{-- Menampilkan catatan terkait status, seperti catatan
                         seleksi dari UMKM, jika tersedia. --}}
                    @if ($entry['note'])
                        <div class="t3">{{ $entry['noteLabel'] }}: {{ $entry['note'] }}</div>
                    @endif

                </div>
            @endforeach

        </div>
    </details>
@endif