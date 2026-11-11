<?php

use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\GyautoController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

/*
| Public endpoints used by the websites (flat7 / joycal / keigayasui / gyauto).
| URLs are the same as the old ichiman API (/api/...). No authentication.
| The admin panel does not use these: it runs on Inertia (routes/web.php).
*/

Route::get('/slide/indexFlat7Joycal', [CatalogController::class, 'slidesFlat7Joycal']);
Route::get('/slide/indexKeigayasui', [CatalogController::class, 'slidesKeigayasui']);
Route::get('/car/allFlat7Joycal', [CatalogController::class, 'carsFlat7Joycal']);
Route::get('/car/allKeigayasui', [CatalogController::class, 'carsKeigayasui']);
Route::get('/photo/list', [CatalogController::class, 'photos']);
Route::get('/photo/list/gyauto', [CatalogController::class, 'photosGyauto']);

// Customer forms: throttled against spam.
Route::middleware('throttle:20,1')->group(function () {
    Route::post('/reservations/add', [ReservationController::class, 'store']);
    // {type} is free-form like before (question / raiten / loan; anything else validates as question).
    Route::post('/gyauto/{type}', [GyautoController::class, 'store']);
});
