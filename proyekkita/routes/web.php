<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Umkm\CandidateSelectionController; // Jangan lupa import controller-nya di atas

Route::get('/', function () {
    return view('welcome');
});


// ROUTE KHUSUS UMKM (PBI-19)
Route::middleware(['auth', 'role:umkm'])->prefix('umkm')->name('umkm.')->group(function () {
    // Route untuk Halaman Daftar Pelamar & Kuota
    Route::get('/seleksi', [CandidateSelectionController::class, 'index'])->name('seleksi.index');
    // Route untuk Halaman Detail Pelamar
    Route::get('/seleksi/{projectId}/pelamar/{applicationId}', [CandidateSelectionController::class, 'show'])->name('seleksi.show');
});