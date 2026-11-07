<?php

//Import semua controller yang dibutuhkan disini
use App\Http\Controllers\CandidateExperienceController;
use App\Http\Controllers\CandidateSkillController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidateSelectionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ApplicationController;
//tambahin ProjectDiscoveryController untuk PBI-14 disini
use App\Http\Controllers\MasterDataController;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;

Route::get('/', function () {
    return view('welcome');
});

//Middleware untuk kandidat
Route::middleware('auth')->prefix('kandidat')->name('kandidat.')->group(function () {
    Route::get('profil', [CandidateSkillController::class, 'edit'])->name('profil.edit');
    Route::get('profil/keterampilan', [CandidateSkillController::class, 'edit'])->name('profil.keterampilan.edit');
    Route::post('profil/keterampilan', [CandidateSkillController::class, 'store'])->name('profil.keterampilan.store');
    Route::put('profil/keterampilan', [CandidateSkillController::class, 'update'])->name('profil.keterampilan.update');
    Route::delete('profil/keterampilan/{skill}', [CandidateSkillController::class, 'destroy'])->name('profil.keterampilan.destroy');

    // PBI-04: kandidat mengelola pengalaman kerja pada profil
    Route::get('profil/pengalaman', [CandidateExperienceController::class, 'edit'])->name('profil.pengalaman.edit');
    Route::post('profil/pengalaman', [CandidateExperienceController::class, 'store'])->name('profil.pengalaman.store');
    Route::put('profil/pengalaman/{experience}', [CandidateExperienceController::class, 'update'])->name('profil.pengalaman.update');
    Route::delete('profil/pengalaman/{experience}', [CandidateExperienceController::class, 'destroy'])->name('profil.pengalaman.destroy');

    // PBI-14: daftar proyek terbuka dan halaman detail proyek
    //kerjakan disini untuk PBI 14

    // PBI-16: kandidat mengajukan lamaran
    Route::post('proyek/{project}/lamaran', [ApplicationController::class, 'store'])
        ->name('proyek.lamaran.store');
});

//Middleware untuk UMKM
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

//Middleware untuk admin
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('data-master', [MasterDataController::class, 'index'])->name('master.index');
    Route::post('data-master/kategori', [MasterDataController::class, 'storeCategory'])->name('master.categories.store');
    Route::put('data-master/kategori/{category}', [MasterDataController::class, 'updateCategory'])->name('master.categories.update');
    Route::patch('data-master/kategori/{category}/status', [MasterDataController::class, 'toggleCategory'])->name('master.categories.toggle');
});




if (app()->isLocal() && file_exists(base_path('routes/dev.php'))) {
    require base_path('routes/dev.php');
}
