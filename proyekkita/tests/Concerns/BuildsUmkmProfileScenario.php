<?php

namespace Tests\Concerns;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Models\Agreement;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * [PBI-01] Pembuat data uji profil usaha UMKM, dipakai bersama oleh Feature Test dan Dusk Test.
 *
 * Memakai BuildsCancellationScenario (makeUmkm, makeKandidat, makeProject, makeApplicationFor) tanpa mengubahnya.
 */
trait BuildsUmkmProfileScenario
{
    use BuildsCancellationScenario;

    /** Isian profil usaha yang sah. $override menimpa sebagian isian. */
    protected function profilePayload(array $override = []): array
    {
        return array_merge([
            'business_name' => 'Kopi Nusantara',
            'business_field' => 'Kuliner & Minuman',
            'description' => 'Roastery kopi lokal.',
            'contact_phone' => '081234567890',
            'city' => 'Bandung',
            'address' => 'Jl. Contoh No. 1, Bandung',
            'nib' => '9120304567812',
            'business_link' => 'instagram.com/kopinusantara',
        ], $override);
    }

    /** Evaluasi (rating) yang DITERIMA $umkm dari seorang kandidat. Ini yang dihitung sebagai rating UMKM. */
    protected function addRatingReceivedByUmkm(User $umkm, int $rating): Evaluation
    {
        $kandidat = $this->makeKandidat();
        $agreement = $this->makeCompletedAgreement($umkm, $kandidat);

        return Evaluation::create([
            'agreement_id' => $agreement->id,
            'reviewer_id' => $kandidat->id,
            'reviewee_id' => $umkm->id,
            'rating' => $rating,
            'comment' => 'Evaluasi uji.',
        ]);
    }

    /** Evaluasi yang DIBERIKAN $umkm kepada kandidat. Tidak boleh ikut dihitung sebagai rating UMKM. */
    protected function addRatingGivenByUmkm(User $umkm, int $rating): Evaluation
    {
        $kandidat = $this->makeKandidat();
        $agreement = $this->makeCompletedAgreement($umkm, $kandidat);

        return Evaluation::create([
            'agreement_id' => $agreement->id,
            'reviewer_id' => $umkm->id,
            'reviewee_id' => $kandidat->id,
            'rating' => $rating,
            'comment' => 'Evaluasi uji dari UMKM.',
        ]);
    }

    private function makeCompletedAgreement(User $umkm, User $kandidat): Agreement
    {
        $project = $this->makeProject(['title' => 'Proyek Rating '.Str::random(8)], $umkm->umkmProfile);
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Accepted, ['project' => $project]);

        return Agreement::create([
            'application_id' => $application->id,
            'scope' => 'Desain sesuai brief',
            'deliverables' => 'File desain final',
            'deadline' => '2026-12-01',
            'total_compensation' => 1000000,
            'revision_limit' => 2,
            'status' => AgreementStatus::Completed,
        ]);
    }
}
