<?php

namespace App\Http\Controllers;

use App\Enums\VerificationStatus;
use App\Http\Requests\VerificationDecisionRequest;
use App\Models\UmkmProfile;
use App\Services\UmkmVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UmkmVerificationController extends Controller
{
    public function __construct(private UmkmVerificationService $verification) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $status = VerificationStatus::tryFrom((string) $request->query('status', 'pending')) ?? VerificationStatus::Pending;

        $counts = UmkmProfile::query()
            ->selectRaw('verification_status, count(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        $queue = UmkmProfile::with('user')
            ->where('verification_status', $status->value)
            ->orderBy('id')
            ->get();

        $selected = $request->filled('usaha')
            ? UmkmProfile::with('user')->find($request->query('usaha'))
            : $queue->first();

        return view('admin.verifikasi.index', compact('status', 'counts', 'queue', 'selected'));
    }

    public function decide(VerificationDecisionRequest $request, UmkmProfile $profile): RedirectResponse
    {
        if ($profile->verification_status !== VerificationStatus::Pending) {
            return redirect()->route('admin.verifikasi.index')
                ->with('error', 'Profil usaha ini sudah diputuskan sebelumnya.');
        }

        $this->verification->decide($profile, $request->validated('decision'), $request->validated('reason'));

        $message = $request->validated('decision') === 'approve'
            ? 'Akun UMKM berhasil diverifikasi.'
            : 'Verifikasi UMKM ditolak.';

        return redirect()->route('admin.verifikasi.index')->with('success', $message);
    }
}
