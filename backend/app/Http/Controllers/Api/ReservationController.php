<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReservationService;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function store(
    Request $request,
    ReservationService $reservationService
) {
    $creator = $request->user();

    // Determinar el residente propietario de la reserva.
    if ($creator->role === 'usuario') {

        // El residente crea su propia reserva.
        $resident = $creator;

    } elseif (
        in_array($creator->role, ['administrador_avanzado', 'administrador', 'operador'])
    ) {

        // Administrador y operador deben indicar el residente.
        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        $resident = User::findOrFail($data['user_id']);

        // Solo se pueden crear reservas para residentes.
        if ($resident->role !== 'usuario') {
            return response()->json([
                'message' => 'Las reservas solo pueden registrarse a nombre de un residente.',
            ], 422);
        }

    } else {

        return response()->json([
            'message' => 'No tienes permisos para registrar reservas.',
        ], 403);
    }

    // Validar los datos de la reserva.
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

    // Crear la reserva a nombre del residente,
    // registrando quién realizó la operación.
    $reservation = $reservationService->create(
        $resident,
        $data,
        $creator
    );

    return response()->json([
        'message' => 'Reserva creada correctamente.',
        'data' => $reservation->load(['user', 'charger', 'createdBy']),
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

    public function update(
    Request $request,
    Reservation $reservation,
    ReservationService $reservationService
) {
    $user = $request->user();

    // Determinar los permisos según el rol.
    $isAdvancedAdmin = $user->role === 'administrador_avanzado';
    $isAdmin = $user->role === 'administrador';
    $isResident = $user->role === 'usuario';

    // El operador no tiene permiso para editar reservas.
    if (!$isAdvancedAdmin && !$isAdmin && !$isResident) {
        return response()->json([
            'message' => 'No tienes permisos para modificar reservas.',
        ], 403);
    }

    // El residente solamente puede editar sus propias reservas.
    if ($isResident && $reservation->user_id !== $user->id) {
        return response()->json([
            'message' => 'No tienes permiso para modificar esta reserva.',
        ], 403);
    }

    // Validar los datos recibidos.
    $data = $request->validate([
        'charger_id' => ['required', 'integer', 'exists:chargers,id'],
        'start_at' => ['required', 'date'],
        'end_at' => ['required', 'date', 'after:start_at'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    // Mantener al residente como propietario de la reserva.
    $updatedReservation = $reservationService->update(
        $reservation->user,
        $reservation,
        $data
    );

    return response()->json([
        'message' => 'Reserva actualizada correctamente.',
        'data' => $updatedReservation,
    ]);
}

    public function destroy(Request $request, Reservation $reservation, ReservationService $reservationService) {
    $user = $request->user();

    // Solo el propietario puede cancelar su reserva.
    if ($reservation->user_id !== $user->id) {
        return response()->json([
            'message' => 'No tienes permiso para cancelar esta reserva.',
        ], 403);
    }

    // Ejecutar la cancelación mediante el servicio.
    $cancelledReservation = $reservationService->cancel(
        $user,
        $reservation
    );

    return response()->json([
        'message' => 'Reserva cancelada correctamente.',
        'data' => $cancelledReservation,
    ]);
}
}
