<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReservationController;

Route::middleware('auth:sanctum')->group(function () {

    Route::post(
        '/reservations',
        [ReservationController::class, 'store']
    );

});
