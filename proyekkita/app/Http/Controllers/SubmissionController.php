<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionNotAllowedException;
use App\Http\Requests\SubmissionRequest;
use App\Models\Agreement;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * [PBI-31 / FR-14] Kandidat menyerahkan hasil pekerjaan (Versi 1) dan melihat Riwayat Versi.
 *
 * Peninjauan oleh UMKM (PBI-32) dan pengiriman versi perbaikan (PBI-33) bukan bagian dari controller ini;
 * keduanya memakai ulang SubmissionService dan SubmissionPolicy.
 * Kesepakatan atau versi milik kandidat lain menghasilkan 404 agar keberadaannya tidak terungkap (BR-31-02).
 */
class SubmissionController extends Controller
{
    public function __construct(private SubmissionService $submissions) {}

    public function index(Agreement $agreement): View
    {
        abort_unless(Gate::allows('viewAny', [Submission::class, $agreement]), 404);

        return view('kandidat.kesepakatan.penyerahan', $this->submissions->page($agreement));
    }

    public function store(SubmissionRequest $request, Agreement $agreement): RedirectResponse
    {
        $back = redirect()->route('kandidat.kesepakatan.penyerahan.index', $agreement);

        try {
            $submission = $this->submissions->submit(
                $agreement,
                $request->descriptionText(),
                $request->resultLink(),
                $request->uploadedFile(),
            );
        } catch (SubmissionNotAllowedException $e) {
            return $back->with('error', $e->getMessage())->withInput($request->except('file'));
        }

        return $back->with(
            'success',
            "Hasil pekerjaan Versi {$submission->version_number} berhasil diserahkan dan berstatus ".SubmissionStatus::Submitted->label().'.'
        );
    }

    /** Unduh/buka berkas suatu versi. PDF dan gambar dibuka di browser, ZIP diunduh. */
    public function file(Agreement $agreement, Submission $submission): Response
    {
        abort_unless((int) $submission->agreement_id === (int) $agreement->id, 404);
        abort_unless(Gate::allows('download', $submission), 404);

        $file = $this->submissions->locateFile($submission);

        if ($file === null) {
            return response()->view('kandidat.kesepakatan.berkas-tidak-ditemukan', ['agreement' => $agreement], 404);
        }

        $disk = Storage::disk($file['disk']);
        $headers = ['X-Content-Type-Options' => 'nosniff'];

        if (in_array($file['mime'], SubmissionService::INLINE_MIMES, true)) {
            return $disk->response($file['path'], $file['name'], $headers);
        }

        return $disk->download($file['path'], $file['name'], $headers);
    }
}
