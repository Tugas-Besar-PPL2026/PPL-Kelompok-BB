<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CandidateExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isKandidat() ?? false;
    }

    public function rules(): array
    {
        return [
            'work_type' => ['required', 'string', 'max:100'],
            'duration' => [
                'required',
                'string',
                'max:50',
                'regex:/^\s*[1-9]\d*(?:[.,]\d+)?\s*(?:tahun|thn|th|bulan|bln|minggu|mgg|hari|hr)(?:\s+[1-9]\d*(?:[.,]\d+)?\s*(?:tahun|thn|th|bulan|bln|minggu|mgg|hari|hr))*\s*$/iu',
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'duration.regex' => 'Format durasi tidak valid (contoh: 6 bulan, 1 tahun).',
        ];
    }

    public function attributes(): array
    {
        return [
            'work_type' => 'Bidang / jenis pekerjaan',
            'duration' => 'Durasi',
            'description' => 'Deskripsi pekerjaan',
        ];
    }
}
