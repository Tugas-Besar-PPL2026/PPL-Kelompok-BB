<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerificationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,reject'],
            'reason'   => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required'  => 'Pilih keputusan verifikasi.',
            'decision.in'        => 'Keputusan tidak valid.',
            'reason.required_if' => 'Alasan penolakan wajib diisi.',
            'reason.max'         => 'Alasan penolakan maksimal 500 karakter.',
        ];
    }
}