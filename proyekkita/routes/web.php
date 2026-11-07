<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidateSelectionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ProjectDiscoveryController;
use App\Http\Controllers\CandidateSkillController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('kandidat')->name('kandidat.')->group(function () {
    Route::get('profil', [CandidateSkillController::class, 'edit'])->name('profil.edit');
    Route::get('profil/keterampilan', [CandidateSkillController::class, 'edit'])->name('profil.keterampilan.edit');
    Route::post('profil/keterampilan', [CandidateSkillController::class, 'store'])->name('profil.keterampilan.store');
    Route::put('profil/keterampilan', [CandidateSkillController::class, 'update'])->name('profil.keterampilan.update');
    Route::delete('profil/keterampilan/{skill}', [CandidateSkillController::class, 'destroy'])->name('profil.keterampilan.destroy');
});

Route::middleware('auth')->prefix('umkm')->name('umkm.')->group(function () {
    Route::get('proyek/buat', [ProjectController::class, 'create'])->name('proyek.create');
    Route::post('proyek', [ProjectController::class, 'store'])->name('proyek.store');
    Route::get('proyek/{project}/ubah', [ProjectController::class, 'edit'])->name('proyek.edit');
    Route::put('proyek/{project}', [ProjectController::class, 'update'])->name('proyek.update');

    Route::get('seleksi', [CandidateSelectionController::class, 'index'])->name('seleksi.index');
    Route::get('seleksi/{application}', [CandidateSelectionController::class, 'show'])
        ->whereNumber('application')->name('seleksi.show');
    Route::get('seleksi/{application}/portofolio/{portfolio}', [CandidateSelectionController::class, 'portfolio'])
        ->whereNumber(['application', 'portfolio'])->name('seleksi.portofolio');
});

Route::middleware('auth')->prefix('kandidat')->name('kandidat.')->group(function () {

    // PBI-14: daftar proyek terbuka dan halaman detail proyek
    Route::get('proyek', [ProjectDiscoveryController::class, 'index'])
        ->name('proyek.index');

    Route::get('proyek/{project}', [ProjectDiscoveryController::class, 'show'])
        ->name('proyek.show');

    // PBI-16: kandidat mengajukan lamaran
    Route::post('proyek/{project}/lamaran', [ApplicationController::class, 'store'])
        ->name('proyek.lamaran.store');

});

if (app()->isLocal() && file_exists(base_path('routes/dev.php'))) {
    require base_path('routes/dev.php');
}