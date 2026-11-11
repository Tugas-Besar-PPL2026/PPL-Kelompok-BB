{{--
    [PBI-17] Riwayat perubahan status dalam bentuk timeline (terbaru di atas). Variabel: $item.
    Bisa dibuka-tutup memakai elemen <details> bawaan HTML, tanpa JavaScript.
    Terbuka otomatis hanya untuk lamaran aktif yang punya lebih dari satu entri ($item['timelineOpen']).
    Bila belum ada riwayat sama sekali, bagian ini tidak ditampilkan (tidak ada data yang dikarang).
--}}
@if (count($item['timeline']) > 0)
    <details class="la-hist"{{ $item['timelineOpen'] ? ' open' : '' }}>
        <summary>Riwayat status ({{ count($item['timeline']) }})</summary>

        <div class="tl">
            @foreach ($item['timeline'] as $entry)
                <div class="it {{ $entry['dot'] }}">
                    <div class="t1">{{ $entry['label'] }}</div>
                    @if ($entry['date'])
                        <div class="t2">{{ $entry['date'] }}</div>
                    @endif
                    @if ($entry['detail'])
                        <div class="t3">{{ $entry['detail'] }}</div>
                    @endif
                    @if ($entry['note'])
                        <div class="t3">{{ $entry['noteLabel'] }}: {{ $entry['note'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </details>
@endif
