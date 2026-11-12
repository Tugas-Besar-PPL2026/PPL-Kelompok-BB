<?php

namespace App\Http\Controllers;

use App\Services\CandidateApplicationService;
use Illuminate\Http\Request;

/**
 * [PBI-17 / FR-10] Halaman Lamaran Saya: kandidat memantau status, catatan seleksi, dan riwayat lamarannya.
 *
 * Hanya menampilkan. Pembatalan lamaran (PBI-18) dan tanggapan kesepakatan (PBI-25) dikerjakan PBI lain
 * dan dipasang lewat partial pada kartu lamaran (lihat resources/views/kandidat/lamaran/_kartu.blade.php).
 */
class CandidateApplicationController extends Controller
{
    public function __construct(private CandidateApplicationService $applications) {}

    public function index(Request $request)
    {
        // Halaman ini khusus kandidat. Middleware peran belum tersedia di repository, jadi dicek di sini.
        abort_unless($request->user()?->isKandidat(), 403, 'Halaman ini hanya untuk kandidat.');

        $profile = $request->user()->candidateProfile;
        $status = $this->applications->statusFromQuery($request->query('status'));
        $counts = $this->applications->counts($profile);

        return view('kandidat.lamaran.index', [
            'status' => $status,
            'counts' => $counts,
            'tabs' => $this->applications->tabs($counts),
            'items' => $this->applications->applications($profile, $status),
        ]);
    }
}
