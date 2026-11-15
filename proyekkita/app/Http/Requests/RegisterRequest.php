<?php

namespace App\Http\Requests;

use App\Services\AuthenticationService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * [FR-01] Validasi formulir registrasi UMKM dan Kandidat.
 * Peran diambil dari parameter route {role}, bukan dari input, sehingga tidak bisa dipalsukan (mis. role=admin).
 * Persetujuan syarat dan ketentuan wajib dicentang, tetapi belum disimpan karena belum ada kolomnya.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->route('role'), AuthenticationService::REGISTRABLE_ROLES, true);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        $nama = $this->route('role') === 'umkm' ? 'Nama penanggung jawab' : 'Nama lengkap';

        return [
            'name.required' => "{$nama} wajib diisi.",
            'name.max' => "{$nama} maksimal 100 karakter.",
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 150 karakter.',
            'email.unique' => 'Email sudah terdaftar. Gunakan email lain atau masuk.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan penggunaan ProyekKita.',
        ];
    }
}
