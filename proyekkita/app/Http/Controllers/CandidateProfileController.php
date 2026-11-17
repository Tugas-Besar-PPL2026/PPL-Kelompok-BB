<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateProfileRequest;
use App\Models\CandidateProfile;
use App\Models\User;
use App\Services\CandidateProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateProfileController extends Controller
{
    public function __construct(private CandidateProfileService $profiles) {}

    public function edit(Request $request): View
    {
        abort_unless($request->user()?->isKandidat(), 403);

        $user = $request->user();
        $profile = $user->candidateProfile?->load(['skills', 'experiences', 'portfolios']);

        // Membuat inisial nama secara otomatis dari nama user yang sedang login
        $navInitials = collect(explode(' ', $user->name ?? ''))
            ->map(fn($segment) => mb_substr($segment, 0, 1))
            ->take(2)
            ->implode('');

        // Variabel ini dibutuhkan partial kandidat.profil._samping (kelengkapan profil).
        return view('kandidat.profil.datadiri', [
            'profile' => $profile,
            'selectedSkills' => $profile?->skills ?? collect(),
            'experiences' => $profile?->experiences ?? collect(),
            'portfolios' => $profile?->portfolios ?? collect(),
            'navInitials' => $navInitials, // <--- Ditambahkan di sini agar tidak error
            // Nilai awal isian Data Diri. Profil yang sudah tersimpan selalu diutamakan; nama dari registrasi
            // (users.name) hanya dipakai sebagai prefill bila profil belum ada. Tidak ada data yang ditulis di sini.
            'prefill' => $this->prefill($user, $profile),
        ]);
    }

    /**
     * Pemetaan data registrasi ke isian profil kandidat:
     * users.name -> candidate_profiles.full_name (keduanya maksimal 100 karakter).
     * Email dan kata sandi tetap hanya di tabel users dan tidak diduplikasi ke profil.
     *
     * @return array{full_name: string}
     */
    private function prefill(User $user, ?CandidateProfile $profile): array
    {
        return [
            'full_name' => filled($profile?->full_name) ? $profile->full_name : (string) $user->name,
        ];
    }

    public function update(CandidateProfileRequest $request): RedirectResponse
    {
        $this->profiles->save(
            $request->user(),
            $request->safe()->except('photo'),
            $request->file('photo'),
        );

        return redirect()->route('kandidat.profil.datadiri')->with('success', 'Data diri berhasil disimpan.');
    }
}