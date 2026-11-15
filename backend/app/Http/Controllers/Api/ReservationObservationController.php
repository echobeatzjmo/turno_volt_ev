<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Mail\ReservationObservationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class ReservationObservationController extends Controller
{
    /**
     * Registrar una observación asociada a una reserva.
     */
    public function store(Request $request, Reservation $reservation)
    {
        $user = $request->user();

        // Verificar permisos según el rol.
        if (!in_array($user->role, [
            'operador',
            'administrador',
            'administrador_avanzado',
        ], true)) {
            return response()->json([
                'message' => 'No tienes permisos para registrar observaciones.',
            ], 403);
        }

        // Validar el contenido de la observación.
        $data = $request->validate([
            'observation' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        // Crear la observación asociada a la reserva.
        $observation = $reservation->observations()->create([
            'user_id' => $user->id,
            'observation' => $data['observation'],
        ]);

        // Cargar las relaciones necesarias para construir el correo.
        $observation->load([
            'user',
            'reservation.user',
            'reservation.charger',
        ]);

        // Consultar los usuarios que tienen el rol administrador.
        $administrators = User::where('role', 'administrador')
            ->whereNotNull('email')
            ->get();

        // Enviar una notificación a cada administrador.
        foreach ($administrators as $administrator) {
            Mail::to($administrator->email)
                ->send(new ReservationObservationMail($observation));
        }

        // Devolver la observación registrada.
        return response()->json([
            'message' => 'Observación registrada correctamente.',
            'data' => $observation,
        ], 201);

    }


    /**
    * Consultar las observaciones de una reserva.
    */
    public function index(Request $request, Reservation $reservation)
    {
        $user = $request->user();

        // Verificar permisos según el rol.
        if (!in_array($user->role, [
            'operador',
            'administrador',
            'administrador_avanzado',
        ], true)) {
            return response()->json([
                'message' => 'No tienes permisos para consultar observaciones.',
        ]   , 403);
        }

        // Consultar las observaciones de la reserva.
        $observations = $reservation->observations()
            ->with('user:id,name,email,role')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Observaciones consultadas correctamente.',
            'data' => $observations,
        ]);
    }

}