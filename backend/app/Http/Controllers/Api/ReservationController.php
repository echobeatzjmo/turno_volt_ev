<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReservationService;
use App\Models\Reservation;
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

    public function index(Request $request)
    {
    $user = $request->user();

    $query = Reservation::with(['user', 'charger']);

    // Los usuarios normales solo consultan sus propias reservas.
    if ($user->role === 'usuario') {
        $query->where('user_id', $user->id);
    }

    // Ordenar las reservas por fecha de inicio.
    $reservations = $query
        ->orderBy('start_at')
        ->get();

    return response()->json([
        'message' => 'Reservas consultadas correctamente.',
        'data' => $reservations,
    ]);
    }

    public function show(Request $request, Reservation $reservation)
    {
    $user = $request->user();

    // El usuario normal solo puede consultar sus propias reservas.
    if (
        $user->role === 'usuario' &&
        $reservation->user_id !== $user->id
    ) {
        return response()->json([
            'message' => 'No tienes permiso para consultar esta reserva.',
        ], 403);
    }

    // Cargar los datos relacionados.
    $reservation->load(['user', 'charger']);

    return response()->json([
        'message' => 'Reserva consultada correctamente.',
        'data' => $reservation,
    ]);
    }
}
