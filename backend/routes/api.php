<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\ReservationObservationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AvailabilityController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas que requieren autenticación
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
        // Gestión de usuarios.
    Route::get('/users', [UserController::class, 'index']);

    Route::post('/users', [UserController::class, 'store']);

    Route::get('/users/{user}', [UserController::class, 'show']);

    Route::put('/users/{user}', [UserController::class, 'update']);

    Route::delete('/users/{user}', [UserController::class, 'destroy']);

        // Consulta de disponibilidad de cargadores
    Route::get('/availability', [AvailabilityController::class, 'index']);

        // Gestión de reservas y observaciones
    Route::get('/reservations', [ReservationController::class, 'index']);

    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);

    Route::post('/reservations', [ReservationController::class, 'store']);

    Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);

    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);

    Route::post('/reservations/{reservation}/observations', [ReservationObservationController::class, 'store']);

    Route::get('/reservations/{reservation}/observations', [ReservationObservationController::class, 'index']);
    
});