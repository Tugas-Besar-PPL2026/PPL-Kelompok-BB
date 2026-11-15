<?php

namespace App\Http\Controllers;

use App\Http\Requests\UmkmProfileRequest;
use App\Services\UmkmProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilUsahaController extends Controller
{
    public function __construct(private UmkmProfileService $profiles) {}

    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->isUmkm(), 403);

        $profile = $user->umkmProfile;
        $ratingCount = $user->receivedEvaluations()->count();
        $distribution = $user->receivedEvaluations()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        return view('umkm.profil.edit', [
            'profile' => $profile,
            'average' => $profile?->averageRating(),
            'ratingCount' => $ratingCount,
            'distribution' => $distribution,
        ]);
    }

    public function update(UmkmProfileRequest $request): RedirectResponse
    {
        [, $reset] = $this->profiles->save($request->user(), $request->validated());

        $message = 'Profil usaha berhasil disimpan.';
        if ($reset) {
            $message .= ' Status verifikasi kembali menjadi Menunggu Pemeriksaan.';
        }

        return redirect()->route('umkm.profil.edit')->with('success', $message);
    }
}