<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Exceptions\AgreementNotAllowedException;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\Project;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * [PBI-24 / FR-12, FR-11] UMKM menyusun dan mengirim kesepakatan pekerjaan untuk pelamar yang sudah Diterima.
 *
 * Alur status: (belum ada) -> Draf -> Dikirim (menunggu persetujuan kandidat).
 * Status Aktif/Ditolak diputuskan kandidat di PBI-25, bukan di sini.
 *
 * Aturan:
 *  - Kesepakatan hanya untuk lamaran berstatus Diterima (PBI-21), paling banyak satu per lamaran (application_id unik).
 *  - Total kompensasi dan termin selalu mengikuti detail proyek (PBI-10); UMKM tidak dapat mengubahnya di sini.
 *  - Isi kesepakatan hanya dapat diubah selama masih Draf. Setelah dikirim tidak dapat diubah atau dikirim ulang.
 *  - Setiap perubahan dijalankan di dalam transaksi dan membaca ulang baris yang dikunci (lockForUpdate),
 *    sehingga dua jendela/permintaan yang bersamaan tidak menghasilkan data ganda.
 */
class AgreementComposeService
{
    public const EXISTS_MESSAGE = 'Kesepakatan untuk lamaran ini sudah ada.';
    public const NOT_ACCEPTED_MESSAGE = 'Kesepakatan hanya dapat disusun untuk pelamar yang sudah diterima.';
    public const ALREADY_SENT_MESSAGE = 'Kesepakatan ini sudah dikirim dan tidak dapat dikirim ulang.';
    public const LOCKED_MESSAGE = 'Kesepakatan yang sudah dikirim tidak dapat diubah lagi.';

    /** Tombol "Susun Kesepakatan" hanya tampil bila lamaran Diterima dan belum memiliki kesepakatan. */
    public function canCompose(Application $application): bool
    {
        return $application->status === ApplicationStatus::Accepted
            && ! $application->agreement()->exists();
    }

    /** Membuat draf kosong untuk lamaran yang sudah Diterima. */
    public function createDraft(Application $application): Agreement
    {
        try {
            return DB::transaction(function () use ($application) {
                $locked = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();

                if ($locked->status !== ApplicationStatus::Accepted) {
                    throw new AgreementNotAllowedException(self::NOT_ACCEPTED_MESSAGE);
                }

                if (Agreement::query()->where('application_id', $locked->id)->exists()) {
                    throw new AgreementNotAllowedException(self::EXISTS_MESSAGE);
                }

                return Agreement::query()->create([
                    'application_id' => $locked->id,
                    'total_compensation' => $this->compensation($locked->project),
                    'status' => AgreementStatus::Draft,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Cadangan bila dua permintaan lolos bersamaan: indeks unik application_id menolak baris kedua.
            throw new AgreementNotAllowedException(self::EXISTS_MESSAGE);
        }
    }

    /**
     * Simpan Draf: isian boleh sebagian. Status tetap Draf.
     *
     * @param  array{scope: ?string, deliverables: ?string, deadline: ?string, revision_limit: ?int}  $data
     */
    public function saveDraft(Agreement $agreement, array $data): Agreement
    {
        return DB::transaction(function () use ($agreement, $data) {
            $locked = $this->lockDraft($agreement, self::LOCKED_MESSAGE);

            $locked->fill($data);
            $locked->total_compensation = $this->compensation($locked->application->project);
            $locked->save();

            return $locked;
        });
    }

    /**
     * Kirim ke Kandidat: semua isian wajib sudah divalidasi oleh AgreementComposeRequest.
     * Status menjadi Dikirim (menunggu persetujuan kandidat) dan waktu kirim dicatat.
     *
     * @param  array{scope: ?string, deliverables: ?string, deadline: ?string, revision_limit: ?int}  $data
     */
    public function send(Agreement $agreement, array $data): Agreement
    {
        return DB::transaction(function () use ($agreement, $data) {
            $locked = $this->lockDraft($agreement, self::ALREADY_SENT_MESSAGE);

            $locked->fill($data);
            $locked->total_compensation = $this->compensation($locked->application->project);
            $locked->status = AgreementStatus::Sent;
            $locked->sent_at = now();
            $locked->save();

            return $locked;
        });
    }

    /** Kunci baris kesepakatan, baca ulang statusnya, dan pastikan masih Draf. */
    private function lockDraft(Agreement $agreement, string $message): Agreement
    {
        $locked = Agreement::query()->whereKey($agreement->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->status !== AgreementStatus::Draft) {
            throw new AgreementNotAllowedException($message);
        }

        return $locked;
    }

    /** Total kompensasi selalu diambil dari detail proyek yang dipublikasikan. */
    private function compensation(Project $project): string
    {
        return (string) $project->total_compensation;
    }
}
