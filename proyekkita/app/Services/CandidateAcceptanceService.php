<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Exceptions\SelectionNotAllowedException;
use App\Models\Application;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * [PBI-21 / FR-13] Menerima pelamar dengan validasi kuota proyek.
 *
 * Hanya lamaran berstatus Diterima yang dihitung terhadap kuota. Pengecekan dilakukan di backend
 * di dalam transaksi dengan mengunci baris proyek, sehingga dua penerimaan bersamaan tidak bisa
 * melewati kuota (efektif pada MySQL/InnoDB). Pembuatan kesepakatan kerja adalah PBI-24, bukan di sini.
 */
class CandidateAcceptanceService
{
    public const QUOTA_FULL_MESSAGE = 'Kuota kandidat untuk proyek ini sudah terpenuhi. Kandidat tidak dapat diterima lagi.';

    public function __construct(private CandidateDecisionService $decisions) {}

    /** @throws SelectionNotAllowedException */
    public function accept(Application $application, ?string $note = null): Application
    {
        return DB::transaction(function () use ($application, $note) {
            // Urutan kunci selalu proyek dulu, baru lamaran, agar tidak saling tunggu (deadlock).
            $project = Project::query()->whereKey($application->project_id)->lockForUpdate()->firstOrFail();
            $locked = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();

            if (! CandidateDecisionService::canTransition($locked->status, ApplicationStatus::Accepted)) {
                throw new SelectionNotAllowedException(
                    CandidateDecisionService::denyMessage($locked->status, ApplicationStatus::Accepted)
                );
            }

            $accepted = Application::query()
                ->where('project_id', $project->id)
                ->where('status', ApplicationStatus::Accepted->value)
                ->count();

            if ($accepted >= (int) $project->quota) {
                throw new SelectionNotAllowedException(self::QUOTA_FULL_MESSAGE);
            }

            $this->decisions->write($locked, ApplicationStatus::Accepted, $note);

            return $locked;
        });
    }
}