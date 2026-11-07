<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route bantuan khusus local development agar membuka http://127.0.0.1:8000 langsung masuk ke halaman portofolio kandidat
$autoLoginKandidat = function () {
    $user = User::where('email', 'kandidat@proyekkita.test')->first();

    if ($user) {
        Auth::login($user);

        return redirect()->route('kandidat.profil.portofolio');
    }

    return response('Jalankan "php artisan migrate:fresh --seed" terlebih dahulu di terminal.', 500);
};

Route::get('/', $autoLoginKandidat);
Route::get('/dashboard', $autoLoginKandidat);
Route::get('/login', $autoLoginKandidat)->name('login');
