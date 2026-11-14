<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

class CandidateSkillService
{
    /** Menambahkan satu keterampilan aktif ke profil kandidat jika belum dipilih. */
    public function addSkill(CandidateProfile $profile, int $skillId): bool
    {
        return DB::transaction(function () use ($profile, $skillId) {
            if ($profile->skills()->whereKey($skillId)->exists()) {
                return false;
            }

            $profile->skills()->attach($skillId);

            return true;
        });
    }

    /** Menghapus satu keterampilan dari profil kandidat. */
    public function removeSkill(CandidateProfile $profile, Skill|int $skill): void
    {
        $skillId = $skill instanceof Skill ? $skill->id : $skill;

        DB::transaction(function () use ($profile, $skillId) {
            $profile->skills()->detach($skillId);
        });
    }

    /** Menyinkronkan daftar keterampilan kandidat dengan pilihan terbaru. */
    public function syncSkills(CandidateProfile $profile, array $skillIds): void
    {
        DB::transaction(function () use ($profile, $skillIds) {
            $uniqueIds = array_values(array_unique(array_map('intval', $skillIds)));
            $profile->skills()->sync($uniqueIds);
        });
    }
}
