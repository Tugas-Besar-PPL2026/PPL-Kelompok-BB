<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [PBI-24] Validasi formulir Susun Kesepakatan Pekerjaan.
 *
 * Tombol yang ditekan dikirim sebagai `aksi`:
 *  - simpan : Simpan Draf. Isian boleh kosong, tetapi yang diisi harus berformat benar.
 *  - kirim  : Kirim ke Kandidat. Ruang lingkup, hasil, tenggat (paling cepat hari ini), dan batas revisi (bilangan bulat >= 0) wajib.
 * Total kompensasi dan termin tidak dikirim lewat form karena selalu diambil dari detail proyek.
 * Hak akses diperiksa lebih dulu (AgreementPolicy::manage), sehingga UMKM lain mendapat 403 sebelum validasi.
 */
class AgreementComposeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->route('agreement')) ?? false;
    }

    public function rules(): array
    {
        $sending = $this->input('aksi') === 'kirim';
        $need = $sending ? 'required' : 'nullable';

        return [
            'aksi' => ['required', Rule::in(['simpan', 'kirim'])],
            'scope' => [$need, 'string', 'max:5000'],
            'deliverables' => [$need, 'string', 'max:5000'],
            'deadline' => array_merge([$need, 'date'], $sending ? ['after_or_equal:today'] : []),
            'revision_limit' => [$need, 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'aksi.required' => 'Pilih Simpan Draf atau Kirim ke Kandidat.',
            'aksi.in' => 'Aksi kesepakatan tidak dikenali.',
            'scope.required' => 'Ruang lingkup pekerjaan wajib diisi.',
            'scope.max' => 'Ruang lingkup pekerjaan maksimal 5000 karakter.',
            'deliverables.required' => 'Hasil yang harus diserahkan wajib diisi.',
            'deliverables.max' => 'Hasil yang harus diserahkan maksimal 5000 karakter.',
            'deadline.required' => 'Tenggat penyelesaian wajib diisi.',
            'deadline.date' => 'Format tenggat penyelesaian tidak valid.',
            'deadline.after_or_equal' => 'Tenggat penyelesaian tidak boleh sebelum hari ini.',
            'revision_limit.required' => 'Batas jumlah revisi wajib diisi.',
            'revision_limit.integer' => 'Batas revisi harus berupa bilangan bulat.',
            'revision_limit.min' => 'Batas revisi minimal 0.',
            'revision_limit.max' => 'Batas revisi maksimal 100.',
        ];
    }

    /** Spasi di awal/akhir dibuang, sehingga isian berisi spasi saja dianggap kosong. */
    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['scope', 'deliverables', 'deadline', 'revision_limit'] as $field) {
            $value = $this->input($field);
            $clean[$field] = is_string($value) ? trim($value) : $value;
        }

        $this->merge($clean);
    }

    /**
     * Isian yang disimpan ke tabel agreements. Isian kosong menjadi null.
     *
     * @return array{scope: ?string, deliverables: ?string, deadline: ?string, revision_limit: ?int}
     */
    public function payload(): array
    {
        $value = fn (string $key) => filled($this->validated($key)) ? $this->validated($key) : null;
        $limit = $value('revision_limit');

        return [
            'scope' => $value('scope'),
            'deliverables' => $value('deliverables'),
            'deadline' => $value('deadline'),
            'revision_limit' => $limit === null ? null : (int) $limit,
        ];
    }

    public function sending(): bool
    {
        return $this->validated('aksi') === 'kirim';
    }
}
