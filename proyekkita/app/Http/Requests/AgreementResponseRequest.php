<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [PBI-25] Validasi panel "Tanggapan Anda".
 *  - tanggapan : setujui | tolak (wajib dipilih)
 *  - alasan    : wajib saat menolak; spasi saja dianggap kosong; diabaikan saat menyetujui
 *  - pemahaman : centang "Saya telah membaca dan memahami ..." wajib saat menyetujui
 * Hak akses diperiksa lebih dulu (AgreementPolicy::respond), sehingga kandidat lain mendapat 403.
 */
class AgreementResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('respond', $this->route('agreement')) ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggapan' => ['required', Rule::in(['setujui', 'tolak'])],
            'alasan' => [
                Rule::requiredIf(fn () => $this->input('tanggapan') === 'tolak'),
                'nullable', 'string', 'max:1000',
            ],
            'pemahaman' => ['accepted_if:tanggapan,setujui'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggapan.required' => 'Pilih tanggapan terlebih dahulu.',
            'tanggapan.in' => 'Tanggapan tidak dikenali.',
            'alasan.required' => 'Alasan penolakan wajib diisi.',
            'alasan.max' => 'Alasan penolakan maksimal 1000 karakter.',
            'pemahaman.accepted_if' => 'Centang pernyataan pemahaman terlebih dahulu.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input('alasan');
        $this->merge(['alasan' => is_string($reason) ? trim($reason) : $reason]);
    }

    public function approving(): bool
    {
        return $this->validated('tanggapan') === 'setujui';
    }

    public function reason(): string
    {
        return trim((string) $this->validated('alasan'));
    }
}
