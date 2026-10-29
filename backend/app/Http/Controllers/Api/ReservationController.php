<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function store(
        Request $request,
        ReservationService $reservationService
    ) {
        // Validar los datos que llegan desde la aplicación.
        $data = $request->validate([
            'charger_id' => [
                'required',
                'integer',
                'exists:chargers,id',
            ],

            'start_at' => [
                'required',
                'date',
            ],

            'end_at' => [
                'required',
                'date',
                'after:start_at',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        // Usuario autenticado.
        $user = $request->user();

        // Delegar las reglas de negocio al servicio.
        $reservation = $reservationService->create(
            $user,
            $data
        );

        return response()->json([
            'message' => 'Reserva creada correctamente.',
            'data' => $reservation,
        ], 201);
    }
}
