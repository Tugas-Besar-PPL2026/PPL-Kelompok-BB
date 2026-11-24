{{--
    [PBI-24, PBI-25, PBI-26] Tabel tahapan pembayaran dari detail proyek (PBI-10). Hanya dibaca.
    Variabel: $terms (AgreementPresenter::terms).
--}}
<div class="tscroll">
    <table class="t" dusk="tabel-termin-kesepakatan">
        <thead>
            <tr>
                <th>Termin</th>
                <th>Kondisi / Target Pembayaran</th>
                <th>Nominal</th>
                <th>Porsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($terms['rows'] as $row)
                <tr>
                    <td class="b nw">Termin {{ $row['no'] }}</td>
                    <td>{{ $row['condition'] }}</td>
                    <td class="b num nw">{{ $row['amount'] }}</td>
                    <td class="mut num">{{ $row['percent'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="sub">Proyek ini belum memiliki skema pembayaran.</td></tr>
            @endforelse
        </tbody>
        @if ($terms['rows'] !== [])
            <tfoot>
                <tr>
                    <td class="b">Total</td>
                    <td></td>
                    <td class="b num nw">{{ $terms['total'] }}</td>
                    <td class="mut num">100%</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
