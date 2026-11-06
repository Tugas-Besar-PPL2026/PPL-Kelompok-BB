<?php

use App\Http\Controllers\CandidateSkillController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('kandidat')->name('kandidat.')->group(function () {
    Route::get('profil', [CandidateSkillController::class, 'edit'])->name('profil.edit');
    Route::get('profil/keterampilan', [CandidateSkillController::class, 'edit'])->name('profil.keterampilan.edit');
    Route::post('profil/keterampilan', [CandidateSkillController::class, 'store'])->name('profil.keterampilan.store');
    Route::put('profil/keterampilan', [CandidateSkillController::class, 'update'])->name('profil.keterampilan.update');
    Route::delete('profil/keterampilan/{skill}', [CandidateSkillController::class, 'destroy'])->name('profil.keterampilan.destroy');
Route::middleware('auth')->prefix('umkm')->name('umkm.')->group(function () {
    Route::get('proyek/buat', [ProjectController::class, 'create'])->name('proyek.create');
    Route::post('proyek', [ProjectController::class, 'store'])->name('proyek.store');
    Route::get('proyek/{project}/ubah', [ProjectController::class, 'edit'])->name('proyek.edit');
    Route::put('proyek/{project}', [ProjectController::class, 'update'])->name('proyek.update');
});

if (app()->isLocal() && file_exists(base_path('routes/dev.php'))) {
    require base_path('routes/dev.php');
}
