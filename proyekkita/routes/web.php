<?php

use App\Http\Controllers\ProfilUsahaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/profil-usaha', [ProfilUsahaController::class, 'edit'])->name('profil-usaha');
Route::post('/profil-usaha', [ProfilUsahaController::class, 'update'])->name('profil-usaha.update');