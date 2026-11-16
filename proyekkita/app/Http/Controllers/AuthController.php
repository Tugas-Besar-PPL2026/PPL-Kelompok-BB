<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** [FR-01] Halaman dan proses Masuk, Registrasi (UMKM / Kandidat), dan Keluar. */
class AuthController extends Controller
{
    public function __construct(private AuthenticationService $auth) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $this->auth->attempt(
            $request->validated('email'),
            $request->validated('password'),
            (string) $request->ip(),
        );

        $request->session()->regenerate();

        // Selalu ke halaman awal sesuai peran, supaya tidak mendarat di halaman yang bukan hak perannya (403).
        return redirect()->route($user->homeRoute());
    }

    public function showRegister(string $role): View
    {
        return view('auth.register', ['role' => $role]);
    }

    public function register(RegisterRequest $request, string $role): RedirectResponse
    {
        $user = $this->auth->register($role, $request->validated());

        $request->session()->regenerate();

        return redirect()->route($user->homeRoute())
            ->with('success', 'Akun berhasil dibuat. Selamat datang di ProyekKita.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
