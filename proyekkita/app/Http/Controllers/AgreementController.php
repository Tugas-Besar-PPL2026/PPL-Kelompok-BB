<?php

namespace App\Http\Controllers;

use App\Enums\AgreementStatus;
use App\Models\Agreement;
use App\Services\AgreementListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * [PBI-26 / FR-12] Melihat Daftar, Detail, dan Status Kesepakatan (UMKM dan Kandidat), mockup 4.5.24 dan 4.5.25.
 *
 *   umkmIndex / umkmShow           -> umkm/kesepakatan/index      (tabel per tab + detail yang dipilih)
 *   candidateIndex / candidateShow -> kandidat/kesepakatan/index  (kartu per tab + detail yang dipilih)
 *
 * Hak akses: UMKM pemilik proyek (AgreementPolicy::manage), kandidat pemilik lamaran (AgreementPolicy::respond).
 * Bukan pemilik -> 403. ID tidak ada -> 404. Draf tidak pernah terlihat oleh kandidat (404).
 * Draf dibuka di halaman Susun Kesepakatan (PBI-24); kesepakatan Dikirim dibuka kandidat di halaman Tanggapi (PBI-25).
 * Fitur progres, pembayaran, dan pembatalan pada mockup adalah PBI lain dan tidak dibuat di sini.
 */
class AgreementController extends Controller
{
    public function __construct(private AgreementListService $agreements) {}

    public function umkmIndex(Request $request): View
    {
        return $this->umkmPage($request->query('status'), null);
    }

    public function umkmShow(Agreement $agreement): View|RedirectResponse
    {
        Gate::authorize('manage', $agreement);

        if ($agreement->status === AgreementStatus::Draft) {
            return redirect()->route('umkm.kesepakatan.edit', $agreement);
        }

        return $this->umkmPage($this->agreements->tabFor($agreement, AgreementListService::UMKM_TABS), $agreement);
    }

    public function candidateIndex(Request $request): View
    {
        return $this->candidatePage($request->query('status'), null);
    }

    public function candidateShow(Agreement $agreement): View|RedirectResponse
    {
        Gate::authorize('respond', $agreement);
        abort_if($agreement->status === AgreementStatus::Draft, 404);

        if ($agreement->status === AgreementStatus::Sent) {
            return redirect()->route('kandidat.kesepakatan.tanggapi', $agreement);
        }

        return $this->candidatePage($this->agreements->tabFor($agreement, AgreementListService::CANDIDATE_TABS), $agreement);
    }

    private function umkmPage(?string $status, ?Agreement $selected): View
    {
        $tabs = AgreementListService::UMKM_TABS;
        $tab = $this->agreements->tabFromRequest($status, $tabs);
        $query = $this->agreements->umkmQuery(request()->user());

        return view('umkm.kesepakatan.index', [
            'tabs' => $tabs,
            'tab' => $tab,
            'counts' => $this->agreements->counts($query, $tabs),
            'items' => $this->agreements->list($query, $tabs[$tab][1]),
            'detail' => $selected ? $this->agreements->detail($selected) : null,
        ]);
    }

    private function candidatePage(?string $status, ?Agreement $selected): View
    {
        $tabs = AgreementListService::CANDIDATE_TABS;
        $tab = $this->agreements->tabFromRequest($status, $tabs);
        $query = $this->agreements->candidateQuery(request()->user());

        return view('kandidat.kesepakatan.index', [
            'tabs' => $tabs,
            'tab' => $tab,
            'counts' => $this->agreements->counts($query, $tabs),
            'items' => $this->agreements->list($query, $tabs[$tab][1]),
            'detail' => $selected ? $this->agreements->detail($selected) : null,
        ]);
    }
}
