<?php

namespace App\Http\Controllers;

use App\Enums\AgreementStatus;
use App\Exceptions\AgreementNotAllowedException;
use App\Http\Requests\AgreementResponseRequest;
use App\Models\Agreement;
use App\Services\AgreementResponseService;
use App\Support\AgreementPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * [PBI-25 / FR-12] Menanggapi Kesepakatan (Kandidat), mockup 4.5.23.
 *
 *   show  : halaman Tawaran Kesepakatan Pekerjaan (hanya untuk kesepakatan berstatus Dikirim)
 *   store : tombol "Kirim Tanggapan" (setujui | tolak dengan alasan)
 *
 * Kesepakatan milik kandidat lain -> 403 (AgreementPolicy::respond).
 * Kesepakatan yang masih Draf belum dikirim UMKM, sehingga dianggap tidak ada -> 404.
 */
class AgreementResponseController extends Controller
{
    public function __construct(private AgreementResponseService $responses) {}

    public function show(Agreement $agreement): View|RedirectResponse
    {
        Gate::authorize('respond', $agreement);
        abort_if($agreement->status === AgreementStatus::Draft, 404);

        if ($agreement->status !== AgreementStatus::Sent) {
            return redirect()->route('kandidat.kesepakatan.show', $agreement);
        }

        $agreement->load(['application.project.umkmProfile', 'application.project.paymentTerms']);
        $project = $agreement->application->project;

        return view('kandidat.kesepakatan.tanggapi', [
            'agreement' => $agreement,
            'project' => $project,
            'umkm' => $project->umkmProfile,
            'code' => AgreementPresenter::code($agreement),
            'terms' => AgreementPresenter::terms($project),
            'deliverables' => AgreementPresenter::deliverables($agreement->deliverables),
            'deadline' => AgreementPresenter::deadline($agreement->deadline),
        ]);
    }

    public function store(AgreementResponseRequest $request, Agreement $agreement): RedirectResponse
    {
        abort_if($agreement->status === AgreementStatus::Draft, 404);

        try {
            $request->approving()
                ? $this->responses->approve($agreement)
                : $this->responses->reject($agreement, $request->reason());
        } catch (AgreementNotAllowedException $e) {
            return redirect()->route('kandidat.kesepakatan.show', $agreement)->with('error', $e->getMessage());
        }

        return redirect()->route('kandidat.kesepakatan.show', $agreement)->with('success', 'Tanggapan berhasil dikirim.');
    }
}
