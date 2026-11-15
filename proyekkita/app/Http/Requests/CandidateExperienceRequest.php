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
            'duration' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
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
