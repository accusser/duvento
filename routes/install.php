<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('/install', [InstallController::class, 'index'])->name('install.index');
    Route::post('/install/locale', [InstallController::class, 'locale'])->name('install.locale');
    Route::post('/install/environment', [InstallController::class, 'environment'])->name('install.environment');
    Route::post('/install/database', [InstallController::class, 'database'])->name('install.database');
    Route::post('/install/migrate', [InstallController::class, 'migrate'])->name('install.migrate');
    Route::post('/install/admin', [InstallController::class, 'admin'])->name('install.admin');
});
