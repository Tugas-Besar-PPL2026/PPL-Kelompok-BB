<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Exceptions\ApplicationNotAllowedException;
use App\Services\ApplicationCancellationService;
use App\Services\CandidateApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * [PBI-18 / FR-10] Kandidat membatalkan lamaran dari halaman Lamaran Saya (PBI-17).
 *
 * Controller ini hanya menerima request dan mengarahkan hasilnya. Seluruh aturan ada di ApplicationCancellationService.
 */
class ApplicationCancellationController extends Controller
{
    public function __construct(
        private ApplicationCancellationService $cancellations,
        private CandidateApplicationService $listing,
    ) {}

    public function __invoke(Request $request, string $application): RedirectResponse
    {
        abort_unless($request->user()?->isKandidat(), 403, 'Aksi ini hanya untuk kandidat.');

        $maxId = (string) PHP_INT_MAX;
        $normalizedId = ltrim($application, '0');

        abort_unless(
            $normalizedId !== ''
            && ctype_digit($application)
            && (
                strlen($normalizedId) < strlen($maxId)
                || (
                    strlen($normalizedId) === strlen($maxId)
                    && strcmp($normalizedId, $maxId) <= 0
                )
            ),
            404
        );

        $applicationId = (int) $normalizedId;

        $status = $this->listing->statusFromQuery($request->input('status'));
        $back = redirect()->route('kandidat.lamaran.index', $status ? ['status' => $status->value] : []);

        try {
            $this->cancellations->cancel($request->user(), $applicationId);
        } catch (ApplicationNotAllowedException $e) {
            return $back->with('error', $e->getMessage());
        }

        return $back->with('success', 'Lamaran berhasil dibatalkan dan berstatus '.ApplicationStatus::Cancelled->label().'.');
    }
}
