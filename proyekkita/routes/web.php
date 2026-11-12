<?php
//Import semua controller yang dibutuhkan disini
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidatePortfolioController;
use App\Http\Controllers\CandidateSkillController;
use App\Http\Controllers\CandidateSelectionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ProjectDiscoveryController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\CandidateApplicationController;
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

    // PBI-05: kandidat mengelola portofolio pada profil
    Route::get('profil/portofolio', [CandidatePortfolioController::class, 'edit'])->name('profil.portofolio');
    Route::post('profil/portofolio', [CandidatePortfolioController::class, 'store'])->name('profil.portofolio.store');
    Route::put('profil/portofolio/{portfolio}', [CandidatePortfolioController::class, 'update'])->name('profil.portofolio.update');
    Route::delete('profil/portofolio/{portfolio}', [CandidatePortfolioController::class, 'destroy'])->name('profil.portofolio.destroy');
    Route::get('profil/portofolio/{portfolio}/berkas', [CandidatePortfolioController::class, 'file'])->name('profil.portofolio.file');

    // PBI-14: daftar proyek terbuka dan halaman detail proyek
    Route::get('proyek', [ProjectDiscoveryController::class, 'index'])
        ->name('proyek.index');

    Route::get('proyek/{project}', [ProjectDiscoveryController::class, 'show'])
        ->name('proyek.show');

    // PBI-16: kandidat mengajukan lamaran
    Route::post('proyek/{project}/lamaran', [ApplicationController::class, 'store'])
        ->name('proyek.lamaran.store');

    // PBI-17: kandidat memantau status dan riwayat lamaran
    Route::get('lamaran', [CandidateApplicationController::class, 'index'])->name('lamaran.index');
});

//Middleware untuk UMKM
Route::middleware('auth')->prefix('umkm')->name('umkm.')->group(function () {
    Route::get('proyek/buat', [ProjectController::class, 'create'])->name('proyek.create');
    Route::post('proyek', [ProjectController::class, 'store'])->name('proyek.store');
    Route::get('proyek/{project}/ubah', [ProjectController::class, 'edit'])->name('proyek.edit');
    Route::put('proyek/{project}', [ProjectController::class, 'update'])->name('proyek.update');

    // Seleksi Kandidat (FR-11, PBI-19): daftar pelamar dan detail pelamar. Akses dibatasi policy ke pemilik proyek.
    Route::get('seleksi', [CandidateSelectionController::class, 'index'])->name('seleksi.index');
    Route::get('seleksi/pelamar/{application}', [CandidateSelectionController::class, 'show'])
        ->whereNumber('application')
        ->name('seleksi.show');
    // PBI-20/21: keputusan seleksi (terpilih sementara, tolak, terima) dan dokumen portofolio pelamar.
    Route::post('seleksi/pelamar/{application}/keputusan', [CandidateSelectionController::class, 'decide'])
        ->whereNumber('application')
        ->name('seleksi.keputusan');
    Route::get('seleksi/pelamar/{application}/portofolio/{portfolio}', [CandidateSelectionController::class, 'portfolioFile'])
        ->whereNumber(['application', 'portfolio'])
        ->name('seleksi.portofolio');
});

//Middleware untuk admin
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('data-master', [MasterDataController::class, 'index'])->name('master.index');
    Route::post('data-master/kategori', [MasterDataController::class, 'storeCategory'])->name('master.categories.store');
    Route::put('data-master/kategori/{category}', [MasterDataController::class, 'updateCategory'])->name('master.categories.update');
    Route::patch('data-master/kategori/{category}/status', [MasterDataController::class, 'toggleCategory'])->name('master.categories.toggle');
});




if (app()->environment(['local', 'dusk.local']) && file_exists(base_path('routes/dev.php'))) {
    require base_path('routes/dev.php');
}