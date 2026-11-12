<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Exceptions\SelectionNotAllowedException;
use App\Models\Application;
use Illuminate\Support\Facades\DB;

/**
 * [PBI-20 / FR-12] Keputusan seleksi UMKM: "Masukkan ke Terpilih Sementara" dan "Tolak dengan Catatan".
 *
 * Penerimaan pelamar (PBI-21, ada pengecekan kuota) ada di CandidateAcceptanceService dan memakai
 * aturan transisi status yang sama lewat canTransition().
 *
 * Aturan transisi:
 *  - Menunggu Keputusan -> Terpilih Sementara, Diterima, Ditolak
 *  - Terpilih Sementara -> Diterima, Ditolak
 *  - Diterima, Ditolak, Dibatalkan -> tidak dapat diubah lagi lewat alur normal
 */
class CandidateDecisionService
{
    /** @return list<ApplicationStatus> status tujuan yang boleh dipilih dari status saat ini */
    public static function allowedTargets(ApplicationStatus $from): array
    {
        return match ($from) {
            ApplicationStatus::Pending => [ApplicationStatus::Shortlisted, ApplicationStatus::Accepted, ApplicationStatus::Rejected],
            ApplicationStatus::Shortlisted => [ApplicationStatus::Accepted, ApplicationStatus::Rejected],
            default => [],
        };
    }

    public static function canTransition(ApplicationStatus $from, ApplicationStatus $to): bool
    {
        return in_array($to, self::allowedTargets($from), true);
    }

    /** Pesan alasan kenapa status saat ini tidak bisa diubah ke status tujuan. */
    public static function denyMessage(ApplicationStatus $from, ApplicationStatus $to): string
    {
        return match ($from) {
            ApplicationStatus::Accepted => 'Pelamar yang sudah diterima tidak dapat diubah statusnya lewat alur ini.',
            ApplicationStatus::Rejected => 'Lamaran ini sudah ditolak dan keputusannya tidak dapat diubah lagi.',
            ApplicationStatus::Cancelled => 'Lamaran ini sudah dibatalkan oleh kandidat sehingga tidak dapat diproses.',
            default => 'Status lamaran ini sudah "'.$from->label().'". Pilih keputusan lain.',
        };
    }

    /** Masukkan ke Terpilih Sementara. Catatan opsional. */
    public function shortlist(Application $application, ?string $note = null): Application
    {
        return $this->apply($application, ApplicationStatus::Shortlisted, $note);
    }

    /** Tolak. Catatan wajib. */
    public function reject(Application $application, ?string $note): Application
    {
        if (trim((string) $note) === '') {
            throw new SelectionNotAllowedException('Catatan penolakan wajib diisi agar kandidat mengetahui alasannya.');
        }

        return $this->apply($application, ApplicationStatus::Rejected, $note);
    }

    /**
     * Mengubah status dalam satu transaksi. Baris lamaran dikunci dan dibaca ulang supaya
     * dua request bersamaan tidak saling menimpa dan keputusan memakai status terbaru.
     */
    private function apply(Application $application, ApplicationStatus $target, ?string $note): Application
    {
        return DB::transaction(function () use ($application, $target, $note) {
            $locked = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();

            if (! self::canTransition($locked->status, $target)) {
                throw new SelectionNotAllowedException(self::denyMessage($locked->status, $target));
            }

            $this->write($locked, $target, $note);

            return $locked;
        });
    }

    /** Menyimpan status, catatan, dan riwayat. Dipanggil di dalam transaksi oleh service ini maupun PBI-21. */
    public function write(Application $application, ApplicationStatus $target, ?string $note): void
    {
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;

        $application->status = $target;
        if ($note !== null) {
            $application->selection_note = $note;
        }
        $application->save();

        $application->statusHistories()->create([
            'status' => $target,
            'note' => $note,
            'changed_at' => now(),
        ]);
    }
}
