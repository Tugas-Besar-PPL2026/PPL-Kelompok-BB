<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UMKM\ProjectMonitoringController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->prefix('umkm')->name('umkm.')->group(function () {
    Route::get('/projects/monitoring', [ProjectMonitoringController::class, 'index'])
        ->name('projects.monitoring');

    Route::get('/projects/monitoring/{id}', [ProjectMonitoringController::class, 'show'])
        ->name('projects.monitoring.detail');
});