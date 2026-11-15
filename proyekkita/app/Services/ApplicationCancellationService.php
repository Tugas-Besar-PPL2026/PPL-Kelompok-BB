<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Exceptions\ApplicationNotAllowedException;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * [PBI-18 / FR-10] Kandidat membatalkan lamaran yang sudah dikirim.
 *
 * Aturan yang berlaku:
 *  1. Hanya pemilik lamaran yang dapat membatalkan. Lamaran yang tidak ada dan lamaran milik kandidat lain
 *     sama-sama menghasilkan "tidak ditemukan" (404), sehingga ID lamaran orang lain tidak bisa ditebak.
 *  2. Hanya lamaran berstatus Menunggu Keputusan atau Terpilih Sementara yang boleh dibatalkan.
 *     Aturan ini memakai Application::isCancellable() yang sudah ada, tanpa aturan tambahan soal status proyek.
 *  3. Status diubah menjadi Dibatalkan dan satu entri riwayat ditulis. Lamaran TIDAK dihapus.
 *
 * Pembatalan bersifat final: unique index (proyek, kandidat) pada tabel applications tetap berlaku,
 * sehingga kandidat tidak dapat melamar lagi pada proyek yang sama (aturan PBI-16, tidak diubah).
 */
class ApplicationCancellationService
{
    /** Catatan pada riwayat status, agar jelas siapa yang membatalkan. */
    public const HISTORY_NOTE = 'Dibatalkan oleh kandidat.';

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException bila lamaran tidak ada atau bukan milik kandidat ini
     * @throws ApplicationNotAllowedException bila status lamaran tidak memungkinkan pembatalan
     */
    public function cancel(User $user, int|string $applicationId): Application
    {
        $profileId = $user->isKandidat() ? $user->candidateProfile?->id : null;

        return DB::transaction(function () use ($profileId, $applicationId) {
            // Kepemilikan menjadi bagian dari query. Baris dikunci lalu dibaca ulang agar keputusan memakai status
            // terbaru (misalnya UMKM baru saja mengubah status) dan dua request bersamaan tidak saling menimpa.
            $application = Application::query()
                ->where('candidate_profile_id', $profileId ?? 0)
                ->lockForUpdate()
                ->findOrFail($applicationId);

            if (! $application->isCancellable()) {
                throw new ApplicationNotAllowedException($this->reasonNotCancellable($application->status));
            }

            $application->status = ApplicationStatus::Cancelled;
            $application->save();

            // Perubahan status dan riwayatnya satu kesatuan: bila salah satu gagal, keduanya dibatalkan.
            $application->statusHistories()->create([
                'status' => ApplicationStatus::Cancelled,
                'note' => self::HISTORY_NOTE,
                'changed_at' => now(),
            ]);

            return $application;
        });
    }

    /** Pesan yang mudah dipahami kandidat sesuai status lamaran saat ini. */
    public function reasonNotCancellable(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::Accepted => 'Lamaran yang sudah diterima tidak dapat dibatalkan.',
            ApplicationStatus::Rejected => 'Lamaran yang sudah ditolak tidak dapat dibatalkan.',
            ApplicationStatus::Cancelled => 'Lamaran ini sudah dibatalkan sebelumnya.',
            default => 'Lamaran dengan status '.$status->label().' tidak dapat dibatalkan.',
        };
    }
}
