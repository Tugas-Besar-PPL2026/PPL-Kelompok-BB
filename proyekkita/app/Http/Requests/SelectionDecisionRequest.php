<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [PBI-20/21] Validasi keputusan seleksi dari panel "Keputusan Seleksi".
 * Hak akses diperiksa lebih dulu lewat policy, sehingga UMKM lain mendapat 403 sebelum validasi dijalankan.
 */
class SelectionDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya UMKM pemilik proyek yang boleh memproses lamaran ini (ApplicationPolicy::decide).
        return $this->user()?->can('decide', $this->route('application')) ?? false;
    }

    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::in(['accept', 'reject', 'shortlist'])],
            // Catatan wajib hanya saat menolak (PBI-20). Saat menerima atau memasukkan ke terpilih sementara opsional.
            'catatan' => [
                Rule::requiredIf(fn () => $this->input('keputusan') === 'reject'),
                'nullable', 'string', 'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'keputusan.required' => 'Pilih salah satu keputusan seleksi terlebih dahulu.',
            'keputusan.in' => 'Keputusan seleksi tidak dikenali.',
            'catatan.required' => 'Catatan penolakan wajib diisi agar kandidat mengetahui alasannya.',
            'catatan.max' => 'Catatan seleksi maksimal 1000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('catatan');
        $this->merge(['catatan' => is_string($note) ? trim($note) : $note]);
    }

    /** Catatan yang sudah dirapikan, atau null bila kosong. */
    public function note(): ?string
    {
        $note = trim((string) $this->validated('catatan'));

        return $note === '' ? null : $note;
    }
}
