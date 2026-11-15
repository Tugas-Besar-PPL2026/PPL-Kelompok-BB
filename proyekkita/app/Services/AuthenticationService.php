<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * [FR-01] Aturan bisnis autentikasi: registrasi UMKM/Kandidat, login dengan pembatasan percobaan, dan logout.
 * Kata sandi di-hash oleh cast 'hashed' pada model User, jadi tidak boleh di-hash ulang di sini.
 */
class AuthenticationService
{
    /** Peran yang boleh dipilih lewat registrasi. Admin disediakan sistem (AdminSeeder) dan tidak bisa mendaftar. */
    public const REGISTRABLE_ROLES = ['umkm', 'kandidat'];

    public const MAX_LOGIN_ATTEMPTS = 5;

    public const LOGIN_DECAY_SECONDS = 60;

    /**
     * Membuat akun baru lalu langsung login. Peran selalu berasal dari route, tidak pernah dari input pengguna.
     * Profil usaha / profil kandidat sengaja tidak dibuat di sini; keduanya dibuat saat formulir profil pertama disimpan.
     * Status verifikasi UMKM tidak diubah (tetap mengikuti mekanisme existing: profil baru berstatus Menunggu Pemeriksaan).
     */
    public function register(string $role, array $data): User
    {
        abort_unless(in_array($role, self::REGISTRABLE_ROLES, true), 404);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::from($role),
        ]);

        Auth::login($user);

        return $user;
    }

    /**
     * Login dengan email dan kata sandi. Gagal login selalu memakai satu pesan umum agar tidak membocorkan
     * apakah email terdaftar. Setelah MAX_LOGIN_ATTEMPTS kegagalan, percobaan berikutnya ditolak sementara.
     *
     * @throws ValidationException pada kunci "credentials"
     */
    public function attempt(string $email, string $password, string $ip): User
    {
        $key = $this->throttleKey($email, $ip);

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'credentials' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            RateLimiter::hit($key, self::LOGIN_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'credentials' => 'Email atau kata sandi salah. Periksa kembali lalu coba lagi.',
            ]);
        }

        RateLimiter::clear($key);

        return Auth::user();
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
    }

    private function throttleKey(string $email, string $ip): string
    {
        return Str::transliterate(Str::lower($email).'|'.$ip);
    }
}
