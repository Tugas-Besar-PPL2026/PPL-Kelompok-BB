{{-- Satu baris termin pada tabel Skema Pembayaran. Variabel: $index, $term (amount, condition), $no. --}}
<tr class="baris-termin">
    <td class="b">Termin <span class="no">{{ $no }}</span></td>
    <td>
        <div class="in @error("terms.$index.condition") err @enderror">
            <input name="terms[{{ $index }}][condition]" maxlength="255" value="{{ $term['condition'] ?? '' }}" placeholder="Contoh: Uang muka setelah kesepakatan berstatus aktif">
        </div>
        @error("terms.$index.condition") <div class="hint err">{{ $message }}</div> @enderror
    </td>
    <td>
        <div class="in @error("terms.$index.amount") err @enderror">
            <span class="mut">Rp</span>
            <input type="number" class="term-amount" name="terms[{{ $index }}][amount]" min="1" max="9999999999" value="{{ $term['amount'] ?? '' }}">
        </div>
        @error("terms.$index.amount") <div class="hint err">{{ $message }}</div> @enderror
    </td>
    <td class="mut num porsi">-</td>
    <td>
        <button type="button" class="btn o sm hapus-termin" title="Hapus termin"><i data-lucide="trash-2" class="ic" width="15" height="15"></i></button>
    </td>
</tr>
