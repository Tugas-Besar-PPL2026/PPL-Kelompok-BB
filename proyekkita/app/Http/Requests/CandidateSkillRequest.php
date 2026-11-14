<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidateSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isKandidat() ?? false;
    }

    public function rules(): array
    {
        $activeSkillRule = Rule::exists('skills', 'id')->where('is_active', true);

        if ($this->isMethod('PUT') || ($this->has('skills') && ! $this->has('skill_id'))) {
            return [
                'skills' => ['present', 'array'],
                'skills.*' => ['integer', $activeSkillRule],
            ];
        }

        return [
            'skill_id' => ['required', 'integer', $activeSkillRule],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib dipilih.',
            'present' => ':attribute wajib dikirim.',
            'array' => ':attribute harus berupa daftar.',
            'integer' => ':attribute tidak valid.',
            'exists' => ':attribute yang dipilih tidak tersedia atau sudah dinonaktifkan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'skill_id' => 'Keterampilan',
            'skills' => 'Keterampilan',
            'skills.*' => 'Keterampilan',
        ];
    }
}
