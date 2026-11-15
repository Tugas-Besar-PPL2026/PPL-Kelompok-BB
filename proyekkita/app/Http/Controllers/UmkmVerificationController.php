<?php

namespace App\Http\Controllers;

use App\Enums\VerificationStatus;
use App\Http\Requests\VerificationDecisionRequest;
use App\Models\UmkmProfile;
use App\Services\UmkmVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** [PBI-08 / FR-05] Admin memverifikasi akun UMKM: antrean per status, detail usaha, keputusan setujui/tolak. */
class UmkmVerificationController extends Controller
{
    public function __construct(private UmkmVerificationService $verification) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $usahaId = $this->numericQuery($request, 'usaha');
        $statusQuery = $request->query('status');

        // Bila hanya ?usaha= yang diberikan (tanpa ?status=), buka antrean sesuai status usaha tersebut
        // supaya detail yang tampil benar-benar usaha yang diminta.
        $focus = ($usahaId !== null && ! is_string($statusQuery))
            ? UmkmProfile::query()->find($usahaId)
            : null;

        $status = $focus?->verification_status
            ?? (is_string($statusQuery) ? VerificationStatus::tryFrom($statusQuery) : null)
            ?? VerificationStatus::Pending;

        $counts = UmkmProfile::query()
            ->selectRaw('verification_status, count(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        $queue = UmkmProfile::with('user')
            ->where('verification_status', $status->value)
            ->orderBy('id')
            ->get();

        $selected = ($usahaId !== null ? $queue->firstWhere('id', $usahaId) : null) ?? $queue->first();

        return view('admin.verifikasi.index', compact('status', 'counts', 'queue', 'selected'));
    }

    /** Tautan langsung ke detail satu usaha: membuka antrean sesuai statusnya dengan usaha tersebut terpilih. */
    public function show(Request $request, UmkmProfile $profile): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return redirect()->route('admin.verifikasi.index', [
            'status' => $profile->verification_status->value,
            'usaha' => $profile->id,
        ]);
    }

    public function decide(VerificationDecisionRequest $request, UmkmProfile $profile): RedirectResponse
    {
        $decision = $request->validated('decision');

        $decided = $this->verification->decide($profile, $decision, $request->validated('reason'));

        if (! $decided) {
            return redirect()->route('admin.verifikasi.index')
                ->with('error', 'Profil usaha ini sudah diputuskan sebelumnya.');
        }

        $message = $decision === 'approve'
            ? 'Akun UMKM berhasil diverifikasi.'
            : 'Verifikasi UMKM ditolak.';

        return redirect()->route('admin.verifikasi.index')->with('success', $message);
    }

    /** Nilai query berupa bilangan bulat positif, atau null bila tidak ada / bukan angka (mis. ?usaha[]=1). */
    private function numericQuery(Request $request, string $key): ?int
    {
        $value = $request->query($key);

        return is_string($value) && ctype_digit($value) && strlen($value) <= 18 ? (int) $value : null;
    }
}
