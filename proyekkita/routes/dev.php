<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



// Login development berdasarkan role
$devLogin = function (string $email, string $redirectUrl) {
    $user = User::where('email', $email)->first();

    if (!$user) {
        return response(
            'User tidak ditemukan. Jalankan php artisan migrate:fresh --seed terlebih dahulu.',
            404
        );
    }

    Auth::login($user);

    return redirect($redirectUrl);
};

// Login sebagai Kandidat
Route::get('/dev/login/kandidat', function () use ($devLogin) {
    return $devLogin(
        'kandidat@proyekkita.test',
        route('kandidat.profil.edit')
    );
});

// Login sebagai UMKM
Route::get('/dev/login/umkm', function () use ($devLogin) {
    return $devLogin(
        'umkm@proyekkita.test',
        '/umkm/seleksi'
    );
});

// Halaman utama sementara langsung masuk sebagai Kandidat
Route::get('/', function () use ($devLogin) {
    return $devLogin(
        'kandidat@proyekkita.test',
        route('kandidat.profil.edit')
    );
});

// Halaman login sementara langsung masuk sebagai Kandidat
Route::get('/login', function () use ($devLogin) {
    return $devLogin(
        'kandidat@proyekkita.test',
        route('kandidat.profil.edit')
    );
})->name('login');

