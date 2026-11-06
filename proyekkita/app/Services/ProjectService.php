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

            $project->fill(Arr::except($data, ['skills']))->save();
            $project->skills()->sync($data['skills']);

            return $project;
        });
    }
}
