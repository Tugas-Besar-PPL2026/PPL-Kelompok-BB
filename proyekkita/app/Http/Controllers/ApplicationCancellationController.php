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

    public function __invoke(Request $request, int $application): RedirectResponse
    {
        // Aksi ini khusus kandidat. Middleware peran belum tersedia di repository, jadi dicek di sini.
        abort_unless($request->user()?->isKandidat(), 403, 'Aksi ini hanya untuk kandidat.');

        // Kembali ke Lamaran Saya pada tab yang sedang aktif (halaman pertama). Nilai status divalidasi
        // memakai enum sehingga isian sembarang diabaikan.
        $status = $this->listing->statusFromQuery($request->input('status'));
        $back = redirect()->route('kandidat.lamaran.index', $status ? ['status' => $status->value] : []);

        try {
            $this->cancellations->cancel($request->user(), $application);
        } catch (ApplicationNotAllowedException $e) {
            return $back->with('error', $e->getMessage());
        }

        return $back->with('success', 'Lamaran berhasil dibatalkan dan berstatus '.ApplicationStatus::Cancelled->label().'.');
    }
}
