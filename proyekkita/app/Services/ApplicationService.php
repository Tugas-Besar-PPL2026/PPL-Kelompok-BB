<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Exceptions\ApplicationNotAllowedException;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * [PBI-16 / FR-09] Logika bisnis pengajuan lamaran oleh kandidat.
 *
 * Aturan yang dijalankan saat lamaran dikirim (submit):
 *  1. Pengguna harus kandidat yang memiliki profil.
 *  2. Kandidat belum pernah melamar proyek yang sama.
 *  3. Proyek masih terbuka (berstatus Dipublikasikan dan tenggat lamaran belum lewat).
 *  4. Profil memenuhi syarat: data pribadi & akademik lengkap dan minimal 1 keterampilan.
 *     Pengalaman kerja dan portofolio opsional.
 */
class ApplicationService
{
    /** Kolom data pribadi & akademik di candidate_profiles yang harus terisi. Foto bersifat opsional. */
    public const PERSONAL_FIELDS = ['full_name', 'headline', 'university', 'major', 'city', 'summary'];

    /**
     * Mengirim lamaran. Dibungkus transaksi dan mengunci baris proyek agar dua request bersamaan
     * tidak menghasilkan lamaran ganda. Unique index applications_unique_1 menjadi pengaman terakhir.
     *
     * @throws ApplicationNotAllowedException jika salah satu aturan bisnis tidak terpenuhi
     */
    public function submit(User $user, Project $project, string $message): Application
    {
        $profile = $user->isKandidat() ? $user->candidateProfile : null;

        if (! $profile) {
            throw new ApplicationNotAllowedException('Hanya kandidat dengan profil yang dapat mengajukan lamaran.');
        }

        try {
            return DB::transaction(function () use ($profile, $project, $message) {
                // Kunci baris proyek dan baca ulang statusnya, supaya keputusan memakai data terbaru.
                $locked = Project::query()->whereKey($project->getKey())->lockForUpdate()->firstOrFail();

                if ($this->existingApplication($profile, $locked)) {
                    throw new ApplicationNotAllowedException('Anda sudah pernah melamar proyek ini. Satu kandidat hanya dapat melamar sekali untuk setiap proyek.');
                }

                if ($reason = $this->closedReason($locked)) {
                    throw new ApplicationNotAllowedException($reason);
                }

                $readiness = $this->profileReadiness($profile);
                if (! $readiness['eligible']) {
                    throw new ApplicationNotAllowedException(
                        'Profil Anda belum memenuhi syarat melamar. Silakan ' . implode(' dan ', $readiness['missing']) . '.'
                    );
                }

                $application = Application::create([
                    'project_id' => $locked->id,
                    'candidate_profile_id' => $profile->id,
                    'message' => $message,
                    'status' => ApplicationStatus::Pending,
                ]);

                // Titik awal riwayat status untuk pemantauan lamaran (PBI-17).
                $application->statusHistories()->create([
                    'status' => ApplicationStatus::Pending,
                    'changed_at' => now(),
                ]);

                return $application;
            });
        } catch (UniqueConstraintViolationException) {
            // Request lain dengan kandidat dan proyek yang sama menyimpan lebih dulu.
            throw new ApplicationNotAllowedException('Anda sudah pernah melamar proyek ini. Satu kandidat hanya dapat melamar sekali untuk setiap proyek.');
        }
    }

    /**
     * Keadaan formulir lamaran untuk halaman Detail Proyek.
     * blocker: null (boleh melamar), 'applied', 'closed', atau 'profile'.
     *
     * @return array{profile: array, closedReason: ?string, existing: ?Application, canApply: bool, blocker: ?string}
     */
    public function formState(User $user, Project $project): array
    {
        $profile = $user->candidateProfile;
        $readiness = $this->profileReadiness($profile);
        $existing = $profile ? $this->existingApplication($profile, $project) : null;
        $closedReason = $this->closedReason($project);

        $blocker = match (true) {
            $existing !== null => 'applied',
            $closedReason !== null => 'closed',
            ! $readiness['eligible'] => 'profile',
            default => null,
        };

        return [
            'profile' => $readiness,
            'closedReason' => $closedReason,
            'existing' => $existing,
            'canApply' => $blocker === null,
            'blocker' => $blocker,
        ];
    }

    /**
     * Pemeriksaan kelayakan profil. Yang diwajibkan hanya data pribadi & akademik dan minimal 1 keterampilan.
     *
     * @return array{personal: bool, skills: int, experiences: int, portfolios: int, eligible: bool, missing: list<string>}
     */
    public function profileReadiness(?CandidateProfile $profile): array
    {
        if (! $profile) {
            return [
                'personal' => false, 'skills' => 0, 'experiences' => 0, 'portfolios' => 0, 'eligible' => false,
                'missing' => ['lengkapi data pribadi & akademik di profil', 'tambahkan minimal 1 keterampilan'],
            ];
        }

        $profile->loadCount(['skills', 'experiences', 'portfolios']);

        $personal = collect(self::PERSONAL_FIELDS)
            ->every(fn (string $field) => trim((string) $profile->{$field}) !== '');

        $missing = [];
        if (! $personal) {
            $missing[] = 'lengkapi data pribadi & akademik';
        }
        if ($profile->skills_count < 1) {
            $missing[] = 'tambahkan minimal 1 keterampilan';
        }

        return [
            'personal' => $personal,
            'skills' => $profile->skills_count,
            'experiences' => $profile->experiences_count,
            'portfolios' => $profile->portfolios_count,
            'eligible' => $missing === [],
            'missing' => $missing,
        ];
    }

    /** Alasan proyek tidak menerima lamaran, atau null jika masih terbuka. */
    public function closedReason(Project $project): ?string
    {
        if ($project->isOpen()) {
            return null;
        }

        return match ($project->status) {
            ProjectStatus::Closed => 'Proyek ini sudah ditutup dan tidak lagi menerima lamaran.',
            ProjectStatus::Published => 'Tenggat lamaran proyek ini sudah berakhir.',
            default => 'Proyek ini tidak sedang menerima lamaran.',
        };
    }

    private function existingApplication(CandidateProfile $profile, Project $project): ?Application
    {
        return Application::query()
            ->where('project_id', $project->getKey())
            ->where('candidate_profile_id', $profile->id)
            ->first();
    }
}
