<?php

namespace App\Services;

use App\Mail\ReservationObservationMail;
use App\Models\ReservationObservation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Notifica a todos los administradores
     * cuando se registra una observación.
     */
    public function notifyAdministratorsOfObservation(
        ReservationObservation $observation
    ): void {
        // Consultar los usuarios que tienen el rol administrador.
        $administrators = User::where('role', 'administrador')
            ->whereNotNull('email')
            ->get();

        // Enviar un correo a cada administrador mediante la cola de Redis.
        foreach ($administrators as $administrator) {
            Mail::to($administrator->email)
                ->queue(new ReservationObservationMail($observation));
        }
    }
}
