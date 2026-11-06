<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('umkm')->name('umkm.')->group(function () {
    Route::get('proyek/buat', [ProjectController::class, 'create'])->name('proyek.create');
    Route::post('proyek', [ProjectController::class, 'store'])->name('proyek.store');
    Route::get('proyek/{project}/ubah', [ProjectController::class, 'edit'])->name('proyek.edit');
    Route::put('proyek/{project}', [ProjectController::class, 'update'])->name('proyek.update');
});

if (app()->isLocal() && file_exists(base_path('routes/dev.php'))) {
    require base_path('routes/dev.php');
}
use App\Http\Controllers\MasterDataController;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('data-master', [MasterDataController::class, 'index'])->name('master.index');
    Route::post('data-master/kategori', [MasterDataController::class, 'storeCategory'])->name('master.categories.store');
    Route::put('data-master/kategori/{category}', [MasterDataController::class, 'updateCategory'])->name('master.categories.update');
    Route::patch('data-master/kategori/{category}/status', [MasterDataController::class, 'toggleCategory'])->name('master.categories.toggle');
});