<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionReviewNotAllowedException;
use App\Models\Agreement;
use App\Models\RevisionRequest;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

/**
 * [PBI-32 / FR-15] UMKM meninjau hasil pekerjaan: menyetujui atau meminta revisi.
 *
 * Aturan saat keputusan disimpan (berurutan, dalam satu transaksi dengan penguncian baris kesepakatan):
 *  1. Kesepakatan berstatus Aktif.                                                     BR-32-03
 *  2. Ada versi terakhir berstatus Menunggu Tinjauan dan sama dengan versi yang dilihat.   BR-32-04
 *  3. Minta revisi hanya bila revisi terpakai < revision_limit (Agreement::canRequestRevision). BR-32-06
 *  4. Setujui -> status approved + review_note opsional + reviewed_at.                   BR-32-05
 *     Minta revisi -> status revision_requested + 1 baris revision_requests + reviewed_at. BR-32-06
 * Status kesepakatan tidak diubah: penyelesaian kesepakatan mengikuti pencatatan pembayaran (PBI-34 dst.).
 */
class SubmissionReviewService
{
    public const MSG_APPROVED = 'Hasil pekerjaan Versi :version disetujui.';

    public const MSG_REVISION = 'Permintaan revisi untuk Versi :version berhasil dikirim kepada kandidat.';

    public const MSG_AGREEMENT_INACTIVE = 'Kesepakatan berstatus :status. Hasil pekerjaan hanya dapat ditinjau pada kesepakatan Aktif.';

    public const MSG_NO_SUBMISSION = 'Kandidat belum menyerahkan hasil pekerjaan.';

    public const MSG_ALREADY_APPROVED = 'Hasil pekerjaan Versi :version sudah disetujui.';

    public const MSG_WAITING_REVISION = 'Versi :version sedang diperbaiki kandidat. Tunggu versi perbaikan untuk ditinjau.';

    public const MSG_STALE = 'Versi yang ditinjau sudah berubah. Muat ulang halaman lalu tinjau versi terbaru.';

    public const MSG_LIMIT_REACHED = 'Batas revisi sudah habis (:used dari :limit). Hasil pekerjaan hanya dapat disetujui.';

    public function __construct(private SubmissionService $submissions) {}

    /**
     * @throws SubmissionReviewNotAllowedException
     */
    public function decide(Agreement $agreement, int $submissionId, string $decision, ?string $note): Submission
    {
        return DB::transaction(function () use ($agreement, $submissionId, $decision, $note) {
            $locked = Agreement::query()->whereKey($agreement->getKey())->lockForUpdate()->firstOrFail();
            $latest = Submission::query()
                ->where('agreement_id', $locked->id)
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->first();

            if ($reason = $this->blockReason($locked, $latest)) {
                throw new SubmissionReviewNotAllowedException($reason);
            }

            if ((int) $latest->id !== $submissionId) {
                throw new SubmissionReviewNotAllowedException(self::MSG_STALE);
            }

            if ($decision === 'revision') {
                if (! $locked->canRequestRevision()) {
                    throw new SubmissionReviewNotAllowedException($this->limitMessage($locked));
                }

                RevisionRequest::create(['submission_id' => $latest->id, 'note' => (string) $note]);
                $latest->update([
                    'status' => SubmissionStatus::RevisionRequested,
                    'review_note' => null,
                    'reviewed_at' => now(),
                ]);
            } else {
                $latest->update([
                    'status' => SubmissionStatus::Approved,
                    'review_note' => $note,
                    'reviewed_at' => now(),
                ]);
            }

            return $latest;
        });
    }

    public function successMessage(Submission $submission): string
    {
        $template = $submission->status === SubmissionStatus::Approved ? self::MSG_APPROVED : self::MSG_REVISION;

        return str_replace(':version', (string) $submission->version_number, $template);
    }

    /** Alasan keputusan tidak dapat diberikan, atau null bila versi terakhir menunggu tinjauan. */
    public function blockReason(Agreement $agreement, ?Submission $latest): ?string
    {
        if ($agreement->status !== AgreementStatus::Active) {
            return str_replace(':status', $agreement->status->label(), self::MSG_AGREEMENT_INACTIVE);
        }

        if ($latest === null) {
            return self::MSG_NO_SUBMISSION;
        }

        return match ($latest->status) {
            SubmissionStatus::Submitted => null,
            SubmissionStatus::Approved => str_replace(':version', (string) $latest->version_number, self::MSG_ALREADY_APPROVED),
            SubmissionStatus::RevisionRequested => str_replace(':version', (string) $latest->version_number, self::MSG_WAITING_REVISION),
        };
    }

    public function limitMessage(Agreement $agreement): string
    {
        return str_replace(
            [':used', ':limit'],
            [(string) $this->submissions->revisionsUsed($agreement), (string) $agreement->revision_limit],
            self::MSG_LIMIT_REACHED
        );
    }

    /** Data halaman Tinjau Hasil Pekerjaan (UMKM). */
    public function page(Agreement $agreement): array
    {
        $agreement->loadMissing(['application.project.umkmProfile', 'application.candidateProfile']);

        $history = $this->submissions->history($agreement);
        $latest = $history->first();
        $used = $this->submissions->revisionsUsed($agreement);
        $limit = (int) $agreement->revision_limit;
        $blocker = $this->blockReason($agreement, $latest);
        $fileRoute = 'umkm.kesepakatan.penyerahan.berkas';

        return [
            'agreement' => $agreement,
            'project' => $agreement->application?->project,
            'code' => 'Kesepakatan #'.$agreement->id,
            'candidateName' => $agreement->application?->candidateProfile?->full_name ?? '-',
            'stages' => $this->submissions->stages($agreement, $latest),
            'latest' => $latest ? $this->submissions->present($agreement, $latest, $fileRoute) : null,
            'latestModel' => $latest,
            'history' => $history->map(fn (Submission $s) => $this->submissions->present($agreement, $s, $fileRoute))->all(),
            'revision' => [
                'used' => $used,
                'limit' => $limit,
                'remaining' => max(0, $limit - $used),
                'percent' => $limit > 0 ? (int) min(100, round($used / $limit * 100)) : 100,
                'canRequest' => $used < $limit,
            ],
            'decision' => [
                'canDecide' => $blocker === null,
                'message' => $blocker,
            ],
        ];
    }
}
