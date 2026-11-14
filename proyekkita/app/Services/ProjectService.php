<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\UmkmProfile;
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
     * Aman dilakukan karena termin hanya bisa diubah selama draf, saat belum ada pembayaran yang mengacu ke termin.
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
}
