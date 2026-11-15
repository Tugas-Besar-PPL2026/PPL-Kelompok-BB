<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CandidateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isKandidat() ?? false;
    }

    public function rules(): array
    {
        return [
            'full_name'  => ['required', 'string', 'max:100'],
            'city'       => ['required', 'string', 'max:100'],
            'headline'   => ['required', 'string', 'max:80'],
            'university' => ['required', 'string', 'max:150'],
            'major'      => ['required', 'string', 'max:100'],
            'summary'    => ['required', 'string', 'max:1000'],
            'photo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'  => 'Nama lengkap wajib diisi.',
            'city.required'       => 'Domisili wajib diisi.',
            'headline.required'   => 'Headline wajib diisi.',
            'headline.max'        => 'Headline maksimal 80 karakter.',
            'university.required' => 'Universitas wajib diisi.',
            'major.required'      => 'Program studi wajib diisi.',
            'summary.required'    => 'Ringkasan diri wajib diisi.',
            'photo.image'         => 'Foto profil harus berupa gambar.',
            'photo.mimes'         => 'Foto profil harus berformat JPG atau PNG.',
            'photo.max'           => 'Ukuran foto profil maksimal 2 MB.',
        ];
    }
}