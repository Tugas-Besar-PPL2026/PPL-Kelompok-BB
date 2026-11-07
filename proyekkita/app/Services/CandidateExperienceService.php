<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\Experience;
use Illuminate\Support\Facades\DB;

class CandidateExperienceService
{
    /** Menambahkan pengalaman kerja baru pada profil kandidat. */
    public function store(CandidateProfile $profile, array $data): Experience
    {
        return DB::transaction(function () use ($profile, $data) {
            return $profile->experiences()->create([
                'work_type' => $data['work_type'],
                'duration' => $data['duration'],
                'description' => (string) ($data['description'] ?? ''),
            ]);
        });
    }

    /** Memperbarui data pengalaman kerja kandidat. */
    public function update(Experience $experience, array $data): Experience
    {
        return DB::transaction(function () use ($experience, $data) {
            $experience->update([
                'work_type' => $data['work_type'],
                'duration' => $data['duration'],
                'description' => (string) ($data['description'] ?? ''),
            ]);

            return $experience;
        });
    }

    /** Menghapus pengalaman kerja dari profil kandidat. */
    public function delete(Experience $experience): void
    {
        DB::transaction(function () use ($experience) {
            $experience->delete();
        });
    }
}
