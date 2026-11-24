<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use App\Models\Submission;
use App\Support\SelectionPresenter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * [PBI-31 / FR-14] Validasi form Kirim Hasil Pekerjaan.
 *
 * Aturan (BR-31-05 s.d. BR-31-08):
 *  - Deskripsi wajib, 1-2000 karakter (dihitung per karakter). Spasi tepi sudah dibuang middleware TrimStrings,
 *    sehingga deskripsi yang hanya berisi spasi dianggap kosong.
 *  - Berkas opsional, satu berkas, PDF/JPG/PNG/ZIP (ekstensi DAN isi/MIME), lebih dari 0 byte, maksimal 25 MB.
 *  - Tautan opsional, URL http/https (tanpa skema dilengkapi https://), maksimal 255 karakter.
 *  - Minimal salah satu: berkas atau tautan.
 *
 * Berkas divalidasi dengan aturan tersendiri (bukan rule bawaan "file/mimes/max") agar setiap kondisi
 * (kosong, gagal unggah, format, ukuran) menghasilkan pesan yang berbeda sesuai acceptance criteria.
 * Kesepakatan milik kandidat lain menghasilkan 404 agar keberadaannya tidak terungkap (BR-31-02).
 */
class SubmissionRequest extends FormRequest
{
    public const MAX_DESCRIPTION = 2000;

    public const MAX_LINK = 255;

    /** 25 MB dalam KB. */
    public const MAX_FILE_KB = 25600;

    /** Ekstensi yang diterima beserta MIME hasil deteksi isi berkas yang cocok. */
    public const MIME_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/x-zip'],
    ];

    public const MSG_DESCRIPTION_REQUIRED = 'Deskripsi hasil pekerjaan wajib diisi.';

    public const MSG_DESCRIPTION_MAX = 'Deskripsi hasil pekerjaan maksimal 2000 karakter.';

    public const MSG_FILE_OR_LINK = 'Isi minimal salah satu: berkas hasil pekerjaan atau tautan hasil pekerjaan.';

    public const MSG_FILE_FORMAT = 'Berkas hasil pekerjaan harus berformat PDF, JPG, PNG, atau ZIP.';

    public const MSG_FILE_MAX = 'Ukuran berkas hasil pekerjaan maksimal 25 MB.';

    public const MSG_FILE_EMPTY = 'Berkas hasil pekerjaan kosong dan tidak dapat diunggah.';

    public const MSG_FILE_FAILED = 'Berkas hasil pekerjaan gagal diunggah. Silakan coba lagi.';

    public const MSG_LINK_INVALID = 'Tautan hasil pekerjaan harus berupa URL http/https yang valid.';

    public const MSG_LINK_MAX = 'Tautan hasil pekerjaan maksimal 255 karakter.';

    public function authorize(): bool
    {
        $agreement = $this->route('agreement');

        abort_unless(
            $agreement instanceof Agreement && Gate::allows('create', [Submission::class, $agreement]),
            404
        );

        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:'.self::MAX_DESCRIPTION],
            'result_link' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                $url = SelectionPresenter::safeUrl(is_string($value) ? $value : null);

                if ($url === null) {
                    $fail(self::MSG_LINK_INVALID);
                } elseif (mb_strlen($url) > self::MAX_LINK) {
                    $fail(self::MSG_LINK_MAX);
                }
            }],
            'file' => ['nullable', function (string $attribute, mixed $value, \Closure $fail) {
                if ($problem = self::fileProblem($value)) {
                    $fail($problem);
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => self::MSG_DESCRIPTION_REQUIRED,
            'description.string' => self::MSG_DESCRIPTION_REQUIRED,
            'description.max' => self::MSG_DESCRIPTION_MAX,
            'result_link.string' => self::MSG_LINK_INVALID,
        ];
    }

    public function attributes(): array
    {
        return [
            'description' => 'Deskripsi hasil pekerjaan',
            'result_link' => 'Tautan hasil pekerjaan',
            'file' => 'Berkas hasil pekerjaan',
        ];
    }

    /** BR-31-05: minimal salah satu berkas atau tautan (diperiksa setelah aturan per kolom). */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['file', 'result_link'])) {
                    return;
                }

                $hasFile = $this->file('file') instanceof UploadedFile;
                $hasLink = trim((string) $this->input('result_link')) !== '';

                if (! $hasFile && ! $hasLink) {
                    $validator->errors()->add('file', self::MSG_FILE_OR_LINK);
                }
            },
        ];
    }

    /**
     * Pemeriksaan berkas unggahan. Mengembalikan pesan galat atau null bila berkas dapat diterima.
     * Urutan: bukan berkas -> kosong -> gagal unggah -> format (ekstensi + MIME isi) -> ukuran.
     */
    public static function fileProblem(mixed $value): ?string
    {
        if (! $value instanceof UploadedFile) {
            return self::MSG_FILE_FORMAT;
        }

        if ($value->getError() === UPLOAD_ERR_OK && (int) $value->getSize() === 0) {
            return self::MSG_FILE_EMPTY;
        }

        if (! $value->isValid()) {
            return self::MSG_FILE_FAILED;
        }

        $extension = strtolower((string) $value->getClientOriginalExtension());
        $mime = (string) $value->getMimeType();

        if (! isset(self::MIME_BY_EXTENSION[$extension]) || ! in_array($mime, self::MIME_BY_EXTENSION[$extension], true)) {
            return self::MSG_FILE_FORMAT;
        }

        if ((int) $value->getSize() > self::MAX_FILE_KB * 1024) {
            return self::MSG_FILE_MAX;
        }

        return null;
    }

    public function descriptionText(): string
    {
        return (string) $this->validated('description');
    }

    /** Tautan yang sudah dinormalisasi (tanpa skema dilengkapi https://), atau null bila tidak diisi. */
    public function resultLink(): ?string
    {
        $link = $this->validated('result_link');

        return is_string($link) ? SelectionPresenter::safeUrl($link) : null;
    }

    public function uploadedFile(): ?UploadedFile
    {
        $file = $this->file('file');

        return $file instanceof UploadedFile ? $file : null;
    }
}
