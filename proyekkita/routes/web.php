<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('umkm')->name('umkm.')->group(function () {
    Route::get('proyek/buat', [ProjectController::class, 'create'])->name('proyek.create');
    Route::post('proyek', [ProjectController::class, 'store'])->name('proyek.store');
});

if (app()->isLocal() && file_exists(base_path('routes/dev.php'))) {
    require base_path('routes/dev.php');
}
