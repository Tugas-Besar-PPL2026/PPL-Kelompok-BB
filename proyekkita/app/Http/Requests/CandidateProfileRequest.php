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
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if (
                        $value instanceof \Illuminate\Http\UploadedFile &&
                        @getimagesize($value->getRealPath()) === false
                    ) {
                        $fail('Isi file foto tidak valid. Unggah gambar JPG atau PNG yang asli.');
                    }
                },
            ],
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
            'full_name.max'       => 'Nama lengkap maksimal 100 karakter.',
            'city.max'            => 'Domisili maksimal 100 karakter.',
            'university.max'      => 'Universitas maksimal 150 karakter.',
            'major.max'           => 'Program studi maksimal 100 karakter.',
            'summary.max'         => 'Ringkasan diri maksimal 1000 karakter.',
            'photo.uploaded'      => 'Foto profil gagal diunggah. Pastikan ukurannya maksimal 2 MB.',
            'photo.image'         => 'Foto profil harus berupa gambar.',
            'photo.mimes'         => 'Foto profil harus berformat JPG atau PNG.',
            'photo.max'           => 'Ukuran foto profil maksimal 2 MB.',
        ];
    }
}