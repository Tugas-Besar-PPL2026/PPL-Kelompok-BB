<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * [PBI-32 / FR-15] Validasi form Keputusan Peninjauan (UMKM).
 *  - decision   : approve (setujui) | revision (minta revisi), wajib.
 *  - note       : wajib saat minta revisi; opsional saat menyetujui; maksimal 2000 karakter (spasi tepi dibuang TrimStrings).
 *  - submission_id : versi yang sedang dilihat UMKM, untuk mencegah keputusan atas versi yang sudah berubah.
 * Kesepakatan pada proyek UMKM lain menghasilkan 404 agar keberadaannya tidak terungkap.
 */
class SubmissionReviewRequest extends FormRequest
{
    public const MAX_NOTE = 2000;

    public const MSG_DECISION_REQUIRED = 'Pilih keputusan peninjauan: setujui atau minta revisi.';

    public const MSG_NOTE_REQUIRED = 'Catatan perbaikan wajib diisi saat meminta revisi.';

    public const MSG_NOTE_MAX = 'Catatan maksimal 2000 karakter.';

    public function authorize(): bool
    {
        $agreement = $this->route('agreement');

        abort_unless(
            $agreement instanceof Agreement && Gate::allows('review', [Submission::class, $agreement]),
            404
        );

        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:approve,revision'],
            'note' => ['nullable', 'string', 'required_if:decision,revision', 'max:'.self::MAX_NOTE],
            'submission_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => self::MSG_DECISION_REQUIRED,
            'decision.string' => self::MSG_DECISION_REQUIRED,
            'decision.in' => self::MSG_DECISION_REQUIRED,
            'note.required_if' => self::MSG_NOTE_REQUIRED,
            'note.string' => self::MSG_NOTE_REQUIRED,
            'note.max' => self::MSG_NOTE_MAX,
            'submission_id.required' => 'Versi yang ditinjau tidak dikenali. Muat ulang halaman.',
            'submission_id.integer' => 'Versi yang ditinjau tidak dikenali. Muat ulang halaman.',
        ];
    }

    public function decision(): string
    {
        return (string) $this->validated('decision');
    }

    public function note(): ?string
    {
        $note = $this->validated('note');

        return is_string($note) && $note !== '' ? $note : null;
    }

    public function submissionId(): int
    {
        return (int) $this->validated('submission_id');
    }
}
