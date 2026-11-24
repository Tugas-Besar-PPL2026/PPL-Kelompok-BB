<?php

namespace App\Http\Controllers;

use App\Exceptions\SubmissionReviewNotAllowedException;
use App\Http\Requests\SubmissionReviewRequest;
use App\Models\Agreement;
use App\Models\Submission;
use App\Services\SubmissionReviewService;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * [PBI-32 / FR-15] UMKM meninjau hasil pekerjaan kandidat: melihat versi terbaru dan riwayat versi,
 * mengunduh berkas, lalu menyetujui atau meminta revisi.
 * Kesepakatan pada proyek UMKM lain menghasilkan 404 (keberadaannya tidak diungkap).
 */
class SubmissionReviewController extends Controller
{
    public function __construct(
        private SubmissionReviewService $reviews,
        private SubmissionService $submissions,
    ) {}

    public function index(Agreement $agreement): View
    {
        abort_unless(Gate::allows('review', [Submission::class, $agreement]), 404);

        return view('umkm.kesepakatan.tinjau', $this->reviews->page($agreement));
    }

    public function store(SubmissionReviewRequest $request, Agreement $agreement): RedirectResponse
    {
        $back = redirect()->route('umkm.kesepakatan.tinjau.index', $agreement);

        try {
            $submission = $this->reviews->decide($agreement, $request->submissionId(), $request->decision(), $request->note());
        } catch (SubmissionReviewNotAllowedException $e) {
            return $back->with('error', $e->getMessage())->withInput();
        }

        return $back->with('success', $this->reviews->successMessage($submission));
    }

    /** UMKM pemilik proyek membuka/mengunduh berkas suatu versi. */
    public function file(Agreement $agreement, Submission $submission): Response
    {
        abort_unless((int) $submission->agreement_id === (int) $agreement->id, 404);
        abort_unless(Gate::allows('download', $submission), 404);

        $file = $this->submissions->locateFile($submission);

        if ($file === null) {
            return response()->view('kandidat.kesepakatan.berkas-tidak-ditemukan', [
                'backUrl' => route('umkm.kesepakatan.tinjau.index', $agreement),
                'backLabel' => 'Kembali ke Tinjau Hasil',
            ], 404);
        }

        return $this->submissions->fileResponse($file);
    }
}
