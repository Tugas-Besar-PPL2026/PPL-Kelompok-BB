<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionNotAllowedException;
use App\Models\Agreement;
use App\Models\Submission;
use App\Support\SelectionPresenter;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * [PBI-31 / FR-14] Logika bisnis penyerahan hasil pekerjaan dan riwayat versi.
 *
 * Aturan yang dijalankan saat penyerahan dikirim (submit), berurutan:
 *  1. Kesepakatan berstatus Aktif (dibaca ulang dengan penguncian baris).            BR-31-03
 *  2. Kesepakatan belum memiliki penyerahan (PBI-31 = penyerahan pertama).          BR-31-04
 *  3. Nomor versi = MAX(version_number) + 1, status awal Menunggu Tinjauan.         BR-31-09, BR-31-10
 *  4. Berkas disimpan di disk privat; dibersihkan bila penyimpanan gagal.            BR-31-12, BR-31-13
 *
 * Dipakai ulang oleh PBI-32 (history(), locateFile(), fileResponse(), stages(), present(), revisionsUsed()) dan PBI-33.
 * Versi perbaikan (PBI-33) cukup mengubah syarat pada submissionBlockReason() tanpa menulis ulang versioning.
 */
class SubmissionService
{
    /** Disk privat (storage/app/private). Berkas hanya dapat diunduh lewat route yang diotorisasi. */
    public const DISK = 'local';

    public const DIRECTORY = 'submissions';

    public const TIMEZONE = 'Asia/Jakarta';

    /** Jenis berkas yang dibuka di browser; jenis lain (ZIP) diunduh. */
    public const INLINE_MIMES = ['application/pdf', 'image/jpeg', 'image/png'];

    public const MSG_FAILED = 'Hasil pekerjaan gagal disimpan. Silakan coba lagi.';

    public const MSG_APPROVED = 'Hasil pekerjaan sudah disetujui UMKM. Penyerahan baru tidak diperlukan.';

    // ------------------------------------------------------------------
    // Penyerahan
    // ------------------------------------------------------------------

    /**
     * Menyimpan penyerahan hasil pekerjaan. Dibungkus transaksi dan mengunci baris kesepakatan agar dua request
     * bersamaan tidak menghasilkan nomor versi ganda. Unique index submissions_unique_1 menjadi pengaman terakhir.
     *
     * @throws SubmissionNotAllowedException bila aturan bisnis tidak terpenuhi atau penyimpanan gagal
     */
    public function submit(Agreement $agreement, string $description, ?string $resultLink, ?UploadedFile $file): Submission
    {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($agreement, $description, $resultLink, $file, &$storedPath) {
                $locked = Agreement::query()->whereKey($agreement->getKey())->lockForUpdate()->firstOrFail();

                $this->ensureCanSubmit($locked);

                $version = $this->nextVersion($locked);

                if ($file !== null) {
                    $storedPath = $this->storeFile($locked, $version, $file);
                }

                return Submission::create([
                    'agreement_id' => $locked->id,
                    'version_number' => $version,
                    'file_path' => $storedPath,
                    'result_link' => $resultLink,
                    'description' => $description,
                    'status' => SubmissionStatus::Submitted,
                    'submitted_at' => now(),
                ]);
            });
        } catch (SubmissionNotAllowedException $e) {
            $this->discardFile($storedPath);

            throw $e;
        } catch (UniqueConstraintViolationException) {
            // Request lain menyimpan versi yang sama lebih dulu: tampilkan alasan sesuai keadaan terbaru.
            $this->discardFile($storedPath);
            $this->ensureCanSubmit($agreement->fresh());

            throw new SubmissionNotAllowedException(self::MSG_FAILED);
        } catch (Throwable $e) {
            $this->discardFile($storedPath);
            report($e);

            throw new SubmissionNotAllowedException(self::MSG_FAILED, 0, $e);
        }
    }

    /** @throws SubmissionNotAllowedException */
    public function ensureCanSubmit(Agreement $agreement): void
    {
        $reason = $this->agreementBlockReason($agreement)
            ?? $this->submissionBlockReason($this->latest($agreement));

        if ($reason !== null) {
            throw new SubmissionNotAllowedException($reason);
        }
    }

    /** BR-31-03: alasan kesepakatan tidak menerima penyerahan, atau null bila Aktif. */
    public function agreementBlockReason(Agreement $agreement): ?string
    {
        return match ($agreement->status) {
            AgreementStatus::Active => null,
            AgreementStatus::Draft, AgreementStatus::Sent => 'Kesepakatan belum aktif. Hasil pekerjaan dapat diserahkan setelah kesepakatan disetujui.',
            AgreementStatus::Rejected => 'Kesepakatan ditolak sehingga hasil pekerjaan tidak dapat diserahkan.',
            AgreementStatus::Completed => 'Kesepakatan sudah selesai. Hasil pekerjaan tidak dapat diserahkan lagi.',
            AgreementStatus::Cancelled => 'Kesepakatan sudah dibatalkan. Hasil pekerjaan tidak dapat diserahkan.',
        };
    }

    /**
     * BR-31-04: PBI-31 hanya menerima penyerahan pertama. Mengembalikan alasan penolakan atau null bila belum ada versi.
     * Versi perbaikan setelah revisi diminta dikirim lewat PBI-33.
     */
    public function submissionBlockReason(?Submission $latest): ?string
    {
        if ($latest === null) {
            return null;
        }

        return match ($latest->status) {
            SubmissionStatus::Submitted => "Hasil pekerjaan Versi {$latest->version_number} sedang ditinjau UMKM. Versi baru belum dapat dikirim.",
            SubmissionStatus::Approved => self::MSG_APPROVED,
            SubmissionStatus::RevisionRequested => "Hasil pekerjaan Versi {$latest->version_number} perlu revisi. Versi perbaikan dikirim melalui form Kirim Versi Perbaikan.",
        };
    }

    /** BR-31-09: nomor versi berikutnya ditentukan server. */
    public function nextVersion(Agreement $agreement): int
    {
        return (int) Submission::query()->where('agreement_id', $agreement->getKey())->max('version_number') + 1;
    }

    public function latest(Agreement $agreement): ?Submission
    {
        return Submission::query()
            ->where('agreement_id', $agreement->getKey())
            ->orderByDesc('version_number')
            ->first();
    }

    // ------------------------------------------------------------------
    // Berkas
    // ------------------------------------------------------------------

    /**
     * BR-31-12: simpan berkas ke submissions/{agreement_id}/v{versi}/{slug-nama-asli}.{ext} di disk privat.
     * Berkas yang sudah ada tidak pernah ditimpa.
     */
    public function storeFile(Agreement $agreement, int $version, UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'hasil-pekerjaan';
        $directory = self::DIRECTORY.'/'.$agreement->getKey().'/v'.$version;
        $name = $base.'.'.$extension;

        if (Storage::disk(self::DISK)->exists($directory.'/'.$name)) {
            $name = $base.'-'.Str::lower(Str::random(6)).'.'.$extension;
        }

        $path = $file->storeAs($directory, $name, self::DISK);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Berkas hasil pekerjaan gagal ditulis ke penyimpanan.');
        }

        return $path;
    }

    /**
     * Lokasi berkas sebuah versi di disk privat, atau null bila versi tanpa berkas / berkas tidak ditemukan.
     *
     * @return array{disk: string, path: string, name: string, size: int, mime: string}|null
     */
    public function locateFile(Submission $submission): ?array
    {
        $path = (string) $submission->file_path;
        $disk = Storage::disk(self::DISK);

        if ($path === '' || str_contains($path, '..') || ! $disk->exists($path)) {
            return null;
        }

        return [
            'disk' => self::DISK,
            'path' => $path,
            'name' => basename($path),
            'size' => (int) $disk->size($path),
            'mime' => (string) ($disk->mimeType($path) ?: ''),
        ];
    }

    /**
     * Respons unduhan berkas sebuah versi (dipakai route kandidat PBI-31 dan route UMKM PBI-32).
     * PDF/JPG/PNG dibuka di browser, jenis lain (ZIP) diunduh; selalu dengan X-Content-Type-Options: nosniff.
     *
     * @param  array{disk: string, path: string, name: string, size: int, mime: string}  $file  hasil locateFile()
     */
    public function fileResponse(array $file): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $disk = Storage::disk($file['disk']);
        $headers = ['X-Content-Type-Options' => 'nosniff'];

        return in_array($file['mime'], self::INLINE_MIMES, true)
            ? $disk->response($file['path'], $file['name'], $headers)
            : $disk->download($file['path'], $file['name'], $headers);
    }

    private function discardFile(?string $path): void
    {
        if ($path === null) {
            return;
        }

        try {
            Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $e) {
            report($e);
        }
    }

    // ------------------------------------------------------------------
    // Tampilan halaman Penyerahan Hasil
    // ------------------------------------------------------------------

    /** Seluruh versi kesepakatan, terbaru di atas, beserta catatan revisi UMKM (BR-31-14). */
    public function history(Agreement $agreement): Collection
    {
        return Submission::query()
            ->where('agreement_id', $agreement->getKey())
            ->with(['revisionRequests' => fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id')])
            ->orderByDesc('version_number')
            ->get();
    }

    /**
     * Keadaan form penyerahan. blocker: null (boleh mengirim), 'agreement', 'submitted', 'approved', 'revision'.
     *
     * @return array{canSubmit: bool, blocker: ?string, message: ?string, nextVersion: int, latest: ?Submission}
     */
    public function formState(Agreement $agreement, ?Submission $latest): array
    {
        $nextVersion = ($latest?->version_number ?? 0) + 1;

        if ($reason = $this->agreementBlockReason($agreement)) {
            return ['canSubmit' => false, 'blocker' => 'agreement', 'message' => $reason, 'nextVersion' => $nextVersion, 'latest' => $latest];
        }

        [$blocker, $message] = match ($latest?->status) {
            null => [null, null],
            SubmissionStatus::Submitted => ['submitted', "Versi {$latest->version_number} sedang ditinjau UMKM."],
            SubmissionStatus::Approved => ['approved', 'Hasil pekerjaan sudah disetujui UMKM.'],
            SubmissionStatus::RevisionRequested => ['revision', "Versi {$latest->version_number} perlu revisi."],
        };

        return ['canSubmit' => $blocker === null, 'blocker' => $blocker, 'message' => $message, 'nextVersion' => $nextVersion, 'latest' => $latest];
    }

    /** Jumlah permintaan revisi yang sudah dipakai pada kesepakatan (FR-15). */
    public function revisionsUsed(Agreement $agreement): int
    {
        return $agreement->revisionRequests()->count();
    }

    /** Data lengkap halaman Penyerahan Hasil untuk kandidat pemilik kesepakatan. */
    public function page(Agreement $agreement): array
    {
        $agreement->loadMissing(['application.project.umkmProfile', 'application.candidateProfile']);

        $history = $this->history($agreement);
        $latest = $history->first();
        $project = $agreement->application?->project;
        $deadline = $agreement->deadline;

        return [
            'agreement' => $agreement,
            'project' => $project,
            'code' => 'Kesepakatan #'.$agreement->id,
            'umkmName' => $project?->umkmProfile?->business_name ?? '-',
            'candidateName' => $agreement->application?->candidateProfile?->full_name ?? '-',
            'deadline' => $deadline ? $this->date($deadline) : null,
            'deadlinePassed' => $deadline !== null && $deadline->copy()->startOfDay()->lt(today()),
            'stages' => $this->stages($agreement, $latest),
            'form' => $this->formState($agreement, $latest),
            'history' => $history->map(fn (Submission $submission) => $this->present($agreement, $submission))->all(),
        ];
    }

    /**
     * Satu baris Riwayat Versi, siap ditampilkan (teks di-escape oleh Blade).
     * $fileRoute: route unduh berkas sesuai peran (kandidat: PBI-31, UMKM: PBI-32).
     * Catatan UMKM: catatan revisi (revision_requests) atau catatan persetujuan (review_note, PBI-32).
     */
    public function present(Agreement $agreement, Submission $submission, string $fileRoute = 'kandidat.kesepakatan.penyerahan.berkas'): array
    {
        $file = $this->locateFile($submission);
        $note = $submission->revisionRequests->first()?->note ?? $submission->review_note;

        return [
            'model' => $submission,
            'version' => $submission->version_number,
            'statusLabel' => $submission->status->label(),
            'statusBadge' => $submission->status->badge(),
            'submittedAt' => $submission->submitted_at ? $this->dateTime($submission->submitted_at) : null,
            'hasFile' => filled($submission->file_path),
            'fileName' => filled($submission->file_path) ? basename((string) $submission->file_path) : null,
            'fileSize' => $file ? SelectionPresenter::fileSize($file['size']) : null,
            'fileUrl' => $file ? route($fileRoute, [$agreement, $submission]) : null,
            'link' => SelectionPresenter::safeUrl($submission->result_link),
            'description' => $submission->description,
            'note' => filled($note) ? $note : null,
            'submittedDate' => $submission->submitted_at ? $this->date($submission->submitted_at->copy()->timezone(self::TIMEZONE)) : null,
            'reviewedAt' => $submission->reviewed_at ? $this->dateTime($submission->reviewed_at) : null,
        ];
    }

    /**
     * Linimasa 5 tahap (Figma). Hanya memakai data yang tersedia; tahap milik PBI lain tampil abu-abu.
     *
     * @return list<array{label: string, note: string, state: string}> state: done | current | todo
     */
    public function stages(Agreement $agreement, ?Submission $latest): array
    {
        $completed = $agreement->status === AgreementStatus::Completed;
        $running = in_array($agreement->status, [AgreementStatus::Active, AgreementStatus::Completed], true);
        $progress = $agreement->progressUpdates()->first();

        $review = match ($latest?->status) {
            null => ['Belum ada penyerahan', 'todo'],
            SubmissionStatus::Submitted => ["Versi {$latest->version_number} menunggu tinjauan", 'current'],
            SubmissionStatus::RevisionRequested => ["Versi {$latest->version_number} perlu revisi", 'current'],
            SubmissionStatus::Approved => ["Versi {$latest->version_number} disetujui", 'done'],
        };

        return [
            [
                'label' => 'Kesepakatan aktif',
                'note' => $agreement->approved_at ? 'Disetujui '.$this->date($agreement->approved_at) : $agreement->status->label(),
                'state' => $running ? 'done' : 'current',
            ],
            [
                'label' => 'Pengerjaan',
                'note' => $progress ? 'Progres '.$progress->percentage.'%' : 'Belum ada laporan progres',
                'state' => $latest !== null || $completed ? 'done' : ($running ? 'current' : 'todo'),
            ],
            ['label' => 'Peninjauan hasil', 'note' => $review[0], 'state' => $review[1]],
            ['label' => 'Pembayaran', 'note' => $completed ? 'Selesai' : 'Menunggu tahapan sebelumnya', 'state' => $completed ? 'done' : 'todo'],
            ['label' => 'Selesai', 'note' => $completed ? 'Kesepakatan selesai' : 'Menunggu tahapan sebelumnya', 'state' => $completed ? 'done' : 'todo'],
        ];
    }

    /** "25 Nov 2026, 21.05 WIB" (data disimpan UTC, ditampilkan WIB). */
    public function dateTime(CarbonInterface $value): string
    {
        return $value->copy()->timezone(self::TIMEZONE)->locale('id')->translatedFormat('j M Y, H.i').' WIB';
    }

    public function date(CarbonInterface $value): string
    {
        return $value->copy()->locale('id')->translatedFormat('j M Y');
    }
}
