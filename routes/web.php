<?php

use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\SlideController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/language', LanguageController::class)->name('language');

    Route::middleware('role:superadmin|admin')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/reservations/export', [ReservationController::class, 'export'])->name('reservations.export');
        Route::resource('reservations', ReservationController::class)->only(['index', 'destroy']);

        // File uploads: forms POST with _method=PUT (multipart can't be sent as PUT).
        Route::resource('photos', PhotoController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('cars', CarController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('slides', SlideController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('stores', StoreController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});
