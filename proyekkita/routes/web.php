<?php

//Import semua controller yang dibutuhkan disini
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateExperienceController;
use App\Http\Controllers\ProfilUsahaController;
use App\Http\Controllers\CandidatePortfolioController;
use App\Http\Controllers\CandidateSkillController;
use App\Http\Controllers\CandidateSelectionController;
use App\Http\Controllers\CandidateProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ProjectDiscoveryController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ProjectModerationController;
use App\Http\Controllers\CandidateApplicationController;
use App\Http\Controllers\ApplicationCancellationController;
use App\Http\Controllers\ProjectMonitoringController;
use App\Http\Controllers\UmkmVerificationController;
use App\Http\Controllers\SubmissionController;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// [FR-01] Halaman publik khusus tamu: landing, masuk, dan registrasi.
// Pengguna yang sudah login diarahkan ke halaman awal sesuai peran (lihat redirectUsersTo di bootstrap/app.php).
Route::middleware('guest')->group(function () {
    Route::get('/', fn () => view('landing'))->name('landing');
    //PBI-44: Login
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');

    // {role}: umkm | kandidat. Peran lain (termasuk admin) tidak cocok dengan route ini dan menghasilkan 404.
    // PBI-43: Registrasi
    Route::get('daftar/{role}', [AuthController::class, 'showRegister'])
        ->whereIn('role', ['umkm', 'kandidat'])
        ->name('register');
    Route::post('daftar/{role}', [AuthController::class, 'register'])
        ->whereIn('role', ['umkm', 'kandidat'])
        ->name('register.store');
});

//PBI-45: logout (POST) hanya untuk pengguna yang sudah login. Logout menghapus session dan cookie, lalu redirect ke landing.
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Middleware untuk kandidat (Menggabungkan rute lama + rute PBI-02 data diri Anda)
Route::middleware(['auth', 'role:kandidat'])->prefix('kandidat')->name('kandidat.')->group(function () {
    // PBI-02: kandidat mengelola data pribadi dan akademik (Milik Anda)
    Route::get('profil/data-diri', [CandidateProfileController::class, 'edit'])->name('profil.datadiri');
    Route::put('profil/data-diri', [CandidateProfileController::class, 'update'])->name('profil.datadiri.update');

    // Rute keterampilan
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

    Route::get('profil/portofolio', [CandidatePortfolioController::class, 'edit'])->name('profil.portofolio');
    Route::post('profil/portofolio', [CandidatePortfolioController::class, 'store'])->name('profil.portofolio.store');
    Route::put('profil/portofolio/{portfolio}', [CandidatePortfolioController::class, 'update'])->name('profil.portofolio.update');
    Route::delete('profil/portofolio/{portfolio}', [CandidatePortfolioController::class, 'destroy'])->name('profil.portofolio.destroy');
    Route::get('profil/portofolio/{portfolio}/berkas', [CandidatePortfolioController::class, 'file'])->name('profil.portofolio.file');


    Route::get('proyek', [ProjectDiscoveryController::class, 'index'])
        ->name('proyek.index');

    Route::get('proyek/{project}', [ProjectDiscoveryController::class, 'show'])
        ->name('proyek.show');

    //PBI-16: kandidat mengajukan lamaran ke proyek terbuka
    Route::post('proyek/{project}/lamaran', [ApplicationController::class, 'store'])
        ->name('proyek.lamaran.store');

    //PBI-17: kandidat memantau status lamaran yang diajukan
    Route::get('lamaran', [CandidateApplicationController::class, 'index'])->name('lamaran.index');

    // PBI-18: kandidat membatalkan lamaran
    Route::patch('lamaran/{application}/batal', ApplicationCancellationController::class)
        ->whereNumber('application')
        ->name('lamaran.cancel');

    // PBI-31: kandidat menyerahkan hasil pekerjaan (Versi 1) dan melihat riwayat versi
    Route::get('kesepakatan/{agreement}/penyerahan', [SubmissionController::class, 'index'])
        ->whereNumber('agreement')
        ->name('kesepakatan.penyerahan.index');
    Route::post('kesepakatan/{agreement}/penyerahan', [SubmissionController::class, 'store'])
        ->whereNumber('agreement')
        ->name('kesepakatan.penyerahan.store');
    Route::get('kesepakatan/{agreement}/penyerahan/{submission}/berkas', [SubmissionController::class, 'file'])
        ->whereNumber(['agreement', 'submission'])
        ->name('kesepakatan.penyerahan.berkas');
});

// Middleware untuk UMKM
Route::middleware(['auth', 'role:umkm'])->prefix('umkm')->name('umkm.')->group(function () {
    // PBI-01: UMKM mengelola profil usaha dan melihat status verifikasi
    Route::get('profil', [ProfilUsahaController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfilUsahaController::class, 'update'])->name('profil.update');

    // PBI-13: UMKM memantau daftar proyek, status, dan hasil moderasi
    // Semua halaman proyek mensyaratkan profil usaha sudah diisi (belum diisi: peringatan + arahkan ke profil).
    Route::middleware('umkm.profile')->group(function () {
        Route::get('proyek', [ProjectMonitoringController::class, 'index'])->name('proyek.index');

        Route::get('proyek/buat', [ProjectController::class, 'create'])->name('proyek.create');
        Route::post('proyek', [ProjectController::class, 'store'])->name('proyek.store');
        Route::get('proyek/{project}/ubah', [ProjectController::class, 'edit'])->name('proyek.edit');
        Route::put('proyek/{project}', [ProjectController::class, 'update'])->name('proyek.update');
    });
    Route::get('proyek', [ProjectMonitoringController::class, 'index'])->name('proyek.index');

    //PBI-9-10-11: UMKM mengelola proyek (buat, ubah, tinjau, ajukan)
    Route::get('proyek/buat', [ProjectController::class, 'create'])->name('proyek.create');
    Route::post('proyek', [ProjectController::class, 'store'])->name('proyek.store');
    Route::get('proyek/{project}/ubah', [ProjectController::class, 'edit'])->name('proyek.edit');
    Route::put('proyek/{project}', [ProjectController::class, 'update'])->name('proyek.update');
    Route::get('proyek/{project}/tinjau', [ProjectController::class, 'review'])->name('proyek.review');
    Route::post('proyek/{project}/ajukan', [ProjectController::class, 'submit'])->name('proyek.submit');


    Route::get('seleksi', [CandidateSelectionController::class, 'index'])->name('seleksi.index');
    Route::get('seleksi/pelamar/{application}', [CandidateSelectionController::class, 'show'])
        ->whereNumber('application')
        ->name('seleksi.show');

    Route::post('seleksi/pelamar/{application}/keputusan', [CandidateSelectionController::class, 'decide'])
        ->whereNumber('application')
        ->name('seleksi.keputusan');
    Route::get('seleksi/pelamar/{application}/portofolio/{portfolio}', [CandidateSelectionController::class, 'portfolioFile'])
        ->whereNumber(['application', 'portfolio'])
        ->name('seleksi.portofolio');
});

// Middleware untuk admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('data-master', [MasterDataController::class, 'index'])->name('master.index');
    Route::post('data-master/kategori', [MasterDataController::class, 'storeCategory'])->name('master.categories.store');
    Route::put('data-master/kategori/{category}', [MasterDataController::class, 'updateCategory'])->name('master.categories.update');
    Route::patch('data-master/kategori/{category}/status', [MasterDataController::class, 'toggleCategory'])->name('master.categories.toggle');

    // PBI-07: keterampilan
    Route::post('data-master/keterampilan', [MasterDataController::class, 'storeSkill'])->name('master.skills.store');
    Route::put('data-master/keterampilan/{skill}', [MasterDataController::class, 'updateSkill'])->name('master.skills.update');
    Route::patch('data-master/keterampilan/{skill}/status', [MasterDataController::class, 'toggleSkill'])->name('master.skills.toggle');

    // PBI-12: moderasi proyek
    Route::get('moderasi-proyek', [ProjectModerationController::class, 'index'])->name('moderasi.index');
    Route::post('moderasi-proyek/{project}', [ProjectModerationController::class, 'decide'])->name('moderasi.decide');
    Route::get('verifikasi', [UmkmVerificationController::class, 'index'])->name('verifikasi.index');
    Route::get('verifikasi/{profile}', [UmkmVerificationController::class, 'show'])
        ->whereNumber('profile')
        ->name('verifikasi.show');
    Route::post('verifikasi/{profile}/keputusan', [UmkmVerificationController::class, 'decide'])
        ->whereNumber('profile')
        ->name('verifikasi.decide');
});

if (app()->environment(['local', 'dusk.local']) && file_exists(base_path('routes/dev.php'))) {
    require base_path('routes/dev.php');
}