<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isUmkm() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['integer', Rule::exists('skills', 'id')->where('is_active', true)],
            'description' => ['required', 'string'],
            'expected_output' => ['required', 'string'],
            'application_deadline' => ['required', 'date', 'after:today'],
            'target_completion_date' => ['required', 'date', 'after:application_deadline'],
            'quota' => ['required', 'integer', 'min:1', 'max:1000'],
            'total_compensation' => ['required', 'numeric', 'min:1', 'max:9999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min.',
            'integer' => ':attribute harus berupa angka.',
            'numeric' => ':attribute harus berupa angka.',
            'date' => ':attribute harus berupa tanggal.',
            'exists' => ':attribute yang dipilih tidak tersedia.',
            'skills.min' => 'Pilih minimal satu keterampilan.',
            'application_deadline.after' => 'Tenggat lamaran paling cepat besok.',
            'target_completion_date.after' => 'Target penyelesaian harus setelah tenggat lamaran.',
            'quota.max' => 'Kuota kandidat maksimal :max orang.',
            'total_compensation.max' => 'Total kompensasi maksimal Rp9.999.999.999.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Judul proyek',
            'category_id' => 'Kategori',
            'skills' => 'Keterampilan',
            'skills.*' => 'Keterampilan',
            'description' => 'Deskripsi pekerjaan',
            'expected_output' => 'Hasil yang diharapkan',
            'application_deadline' => 'Tenggat lamaran',
            'target_completion_date' => 'Target penyelesaian',
            'quota' => 'Kuota kandidat',
            'total_compensation' => 'Total kompensasi',
        ];
    }
}
