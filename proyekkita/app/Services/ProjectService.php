<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\UmkmProfile;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;


class ProjectService
{
    /** Status proyek yang masih boleh diubah sebagai draf. */
    public const EDITABLE = [ProjectStatus::Draft, ProjectStatus::RevisionRequested];

    /** Membuat draf baru, atau memperbarui draf yang sudah ada kalau $project diisi. */
    public function saveDraft(UmkmProfile $umkm, array $data, ?Project $project = null): Project
    {
        return DB::transaction(function () use ($umkm, $data, $project) {
            $project ??= new Project([
                'umkm_profile_id' => $umkm->id,
                'status' => ProjectStatus::Draft,
            ]);

            $project->fill(Arr::except($data, ['skills', 'payment_method', 'terms']))->save();
            $project->skills()->sync($data['skills']);
            $this->syncPaymentTerms($project, $data['terms']);

            return $project;
        });
    }
    /**
     * Mengganti seluruh termin proyek dengan termin dari form, bernomor urut mulai 1.
     */
    public function syncPaymentTerms(Project $project, array $terms): void
    {
        $project->paymentTerms()->delete();

        foreach (array_values($terms) as $i => $term) {
            $project->paymentTerms()->create([
                'term_number' => $i + 1,
                'amount' => $term['amount'],
                'condition' => $term['condition'],
            ]);
        }
    }

    /**
     * Syarat pengajuan moderasi beserta status terpenuhi atau belum.
     * Dipakai untuk panel Syarat Pengajuan dan diperiksa ulang saat proyek diajukan.
     *
     * @return array<string, array{label: string, ok: bool, note: string}>
     */
    public function submissionChecks(Project $project): array
    {
        $project->loadMissing('umkmProfile', 'skills', 'paymentTerms');

        $umkm = $project->umkmProfile;
        $deadline = $project->application_deadline;
        $target = $project->target_completion_date;
        $termTotal = (float) $project->paymentTerms->sum('amount');
        $date = fn ($value) => $value ? $value->locale('id')->translatedFormat('d M Y') : '-';

        $requiredFields = [
            filled($project->title),
            filled($project->category_id),
            $project->skills->isNotEmpty(),
            filled($project->description),
            filled($project->expected_output),
            filled($deadline),
            filled($target),
            $project->quota > 0,
            $project->total_compensation > 0,
        ];
        $filledCount = count(array_filter($requiredFields));

        return [
            'verified' => [
                'label' => 'Akun UMKM sudah terverifikasi',
                'ok' => $umkm->isVerified(),
                'note' => $umkm->isVerified()
                    ? ($umkm->verified_at ? 'Terverifikasi sejak ' . $date($umkm->verified_at) : 'Terverifikasi')
                    : 'Menunggu verifikasi admin',
            ],
            'complete' => [
                'label' => 'Semua kolom wajib terisi',
                'ok' => $filledCount === count($requiredFields),
                'note' => $filledCount . ' dari ' . count($requiredFields) . ' kolom',
            ],
            'deadline' => [
                'label' => 'Tenggat lamaran setelah hari ini',
                'ok' => $deadline !== null && $deadline->isAfter(today()),
                'note' => $date($deadline),
            ],
            'target' => [
                'label' => 'Target penyelesaian setelah tenggat lamaran',
                'ok' => $deadline !== null && $target !== null && $target->isAfter($deadline),
                'note' => $date($target),
            ],
            'terms' => [
                'label' => 'Total termin sama dengan total kompensasi',
                'ok' => $project->paymentTerms->isNotEmpty() && round($termTotal, 2) === round((float) $project->total_compensation, 2),
                'note' => 'Rp' . number_format($termTotal, 0, ',', '.'),
            ],
        ];
    }

    /**
     * Mengajukan proyek ke admin: status menjadi Menunggu Moderasi dan setiap admin mendapat notifikasi.
     *
     * @throws ValidationException jika proyek sudah diajukan atau syarat pengajuan belum terpenuhi
     */
    public function submitForModeration(Project $project): void
    {
        if (! in_array($project->status, self::EDITABLE, true)) {
            throw ValidationException::withMessages(['ajukan' => 'Proyek ini sudah diajukan atau sudah diproses admin.']);
        }

        $unmet = collect($this->submissionChecks($project))->reject(fn (array $check) => $check['ok']);

        if ($unmet->isNotEmpty()) {
            throw ValidationException::withMessages([
                'ajukan' => 'Syarat pengajuan belum terpenuhi: ' . $unmet->pluck('label')->implode(', ') . '.',
            ]);
        }

        DB::transaction(function () use ($project) {
            // Status hanya diubah jika masih draf atau perlu perbaikan, sehingga double click tidak mengirim notifikasi dua kali.
            $updated = Project::whereKey($project->id)
                ->whereIn('status', array_map(fn (ProjectStatus $status) => $status->value, self::EDITABLE))
                ->update(['status' => ProjectStatus::PendingReview->value]);

            if ($updated === 0) {
                throw ValidationException::withMessages(['ajukan' => 'Proyek ini sudah diajukan atau sudah diproses admin.']);
            }

            $project->status = ProjectStatus::PendingReview;

            User::where('role', UserRole::Admin->value)->pluck('id')->each(fn (int $adminId) => Notification::create([
                'user_id' => $adminId,
                'type' => 'proyek',
                'message' => "Proyek baru menunggu moderasi: {$project->title}",
                'related_type' => Project::class,
                'related_id' => $project->id,
            ]));
        });
    }
}
