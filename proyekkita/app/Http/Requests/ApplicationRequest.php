<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [PBI-16] Validasi form Kirim Lamaran. Hanya kandidat yang boleh mengirim.
 * Spasi di awal/akhir sudah dibuang otomatis oleh Laravel sebelum validasi,
 * jadi pesan yang hanya berisi spasi dianggap kosong.
 */
class ApplicationRequest extends FormRequest
{
    /** Batas minimal pesan lamaran (karakter) sesuai proposal FR-09 / mockup. */
    public const MIN_MESSAGE = 50;

    /** Batas teknis agar muat di kolom TEXT database. */
    public const MAX_MESSAGE = 5000;

    public function authorize(): bool
    {
        return $this->user()?->isKandidat() ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:' . self::MIN_MESSAGE, 'max:' . self::MAX_MESSAGE],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Pesan lamaran wajib diisi.',
            'message.min' => 'Pesan lamaran minimal :min karakter.',
            'message.max' => 'Pesan lamaran maksimal :max karakter.',
        ];
    }

    public function attributes(): array
    {
        return ['message' => 'Pesan lamaran'];
    }
}
