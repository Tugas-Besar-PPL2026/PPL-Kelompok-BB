<?php

namespace App\Http\Controllers;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Exceptions\AgreementNotAllowedException;
use App\Http\Requests\AgreementComposeRequest;
use App\Models\Agreement;
use App\Models\Application;
use App\Services\AgreementComposeService;
use App\Support\AgreementPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * [PBI-24 / FR-12, FR-11] Menyusun dan Mengirim Kesepakatan Pekerjaan (UMKM), mockup 4.5.22.
 *
 *   store  : tombol "Susun Kesepakatan" di Detail Pelamar (PBI-19) -> membuat draf
 *   edit   : halaman Susun Kesepakatan Pekerjaan (hanya selama Draf)
 *   update : tombol "Simpan Draf" atau "Kirim ke Kandidat"
 *
 * Hak akses: hanya UMKM pemilik proyek (ApplicationPolicy::view untuk lamaran, AgreementPolicy::manage untuk kesepakatan).
 */
class AgreementComposeController extends Controller
{
    public function __construct(private AgreementComposeService $compose) {}

    public function store(Application $application): RedirectResponse
    {
        Gate::authorize('view', $application);

        try {
            $agreement = $this->compose->createDraft($application);
        } catch (AgreementNotAllowedException $e) {
            // Kesepakatan sudah dibuat dari jendela lain: arahkan ke kesepakatan yang sudah ada.
            $existing = $application->agreement()->first();

            return $existing
                ? redirect()->route('umkm.kesepakatan.edit', $existing)->with('error', $e->getMessage())
                : redirect()->route('umkm.seleksi.show', $application)->with('error', $e->getMessage());
        }

        return redirect()->route('umkm.kesepakatan.edit', $agreement)
            ->with('success', 'Draf kesepakatan berhasil dibuat. Lengkapi isinya lalu kirim ke kandidat.');
    }

    public function edit(Agreement $agreement): View|RedirectResponse
    {
        Gate::authorize('manage', $agreement);

        if ($agreement->status !== AgreementStatus::Draft) {
            return redirect()->route('umkm.kesepakatan.show', $agreement)
                ->with('warning', AgreementComposeService::LOCKED_MESSAGE);
        }

        $agreement->load([
            'application.candidateProfile',
            'application.statusHistories',
            'application.project.paymentTerms',
        ]);

        $application = $agreement->application;
        $acceptedAt = $application->statusHistories
            ->first(fn ($history) => $history->status === ApplicationStatus::Accepted)?->changed_at;

        return view('umkm.kesepakatan.susun', [
            'agreement' => $agreement,
            'application' => $application,
            'project' => $application->project,
            'candidate' => $application->candidateProfile,
            'acceptedAt' => $acceptedAt,
            'terms' => AgreementPresenter::terms($application->project),
            'code' => AgreementPresenter::code($agreement),
        ]);
    }

    public function update(AgreementComposeRequest $request, Agreement $agreement): RedirectResponse
    {
        try {
            if ($request->sending()) {
                $this->compose->send($agreement, $request->payload());

                return redirect()->route('umkm.kesepakatan.show', $agreement)
                    ->with('success', 'Kesepakatan berhasil dikirim ke kandidat.');
            }

            $this->compose->saveDraft($agreement, $request->payload());
        } catch (AgreementNotAllowedException $e) {
            return redirect()->route('umkm.kesepakatan.show', $agreement)->with('error', $e->getMessage());
        }

        return redirect()->route('umkm.kesepakatan.edit', $agreement)
            ->with('success', 'Draf kesepakatan berhasil disimpan.');
    }
}
