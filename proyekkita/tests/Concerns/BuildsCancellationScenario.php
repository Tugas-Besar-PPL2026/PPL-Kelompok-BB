<?php

namespace Tests\Concerns;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * [PBI-18] Pembuat data uji untuk pembatalan lamaran, dipakai bersama oleh Feature Test dan Dusk Test.
 *
 * Trait ini memakai BuildsApplicationScenario (makeKandidat, makeProject, validMessage) tanpa mengubahnya.
 * Kelas test cukup memakai trait ini saja.
 */
trait BuildsCancellationScenario
{
    use BuildsApplicationScenario;

    /**
     * Membuat lamaran milik $kandidat dengan status tertentu pada proyek baru (atau $options['project']).
     * Riwayat selalu diawali entri Menunggu Keputusan seperti yang dicatat PBI-16.
     *
     * Opsi: title, project, created_at, selection_note, histories (daftar [status, changed_at, note]).
     */
    protected function makeApplicationFor(User $kandidat, ApplicationStatus $status = ApplicationStatus::Pending, array $options = []): Application
    {
        $project = $options['project'] ?? $this->makeProject(['title' => $options['title'] ?? 'Proyek '.Str::random(8)]);
        $createdAt = $options['created_at'] ?? '2026-11-04 10:00:00';

        $application = new Application();
        $application->forceFill([
            'project_id' => $project->id,
            'candidate_profile_id' => $kandidat->candidateProfile->id,
            'message' => $this->validMessage(),
            'status' => $status,
            'selection_note' => $options['selection_note'] ?? null,
            'created_at' => $createdAt,
        ])->save();

        ApplicationStatusHistory::create([
            'application_id' => $application->id, 'status' => ApplicationStatus::Pending, 'note' => null, 'changed_at' => $createdAt,
        ]);

        foreach ($options['histories'] ?? [] as [$historyStatus, $changedAt, $note]) {
            ApplicationStatusHistory::create([
                'application_id' => $application->id, 'status' => $historyStatus, 'note' => $note, 'changed_at' => $changedAt,
            ]);
        }

        return $application->fresh();
    }

    protected function historyCountOf(Application $application): int
    {
        return ApplicationStatusHistory::where('application_id', $application->id)->count();
    }
}
