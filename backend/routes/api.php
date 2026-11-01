<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas que requieren autenticación
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/reservations', [ReservationController::class, 'index']);

    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);

    Route::post('/reservations', [ReservationController::class, 'store']);

    Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);

    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);

});