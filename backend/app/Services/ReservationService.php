<?php

namespace App\Services;

use App\Models\Charger;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function create(User $user, array $data): Reservation
    {
        return DB::transaction(function () use ($user, $data) {

            // Bloquear al usuario para evitar reservas simultáneas.
            User::whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Bloquear el cargador para evitar sobrecupos.
            $charger = Charger::whereKey($data['charger_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!$charger->is_active) {
                throw ValidationException::withMessages([
                    'charger_id' => 'El cargador no está activo.',
                ]);
            }

            // Interpretar las fechas en la zona horaria de Colombia.
            $start = Carbon::parse(
                $data['start_at'],
                'America/Bogota'
            );

            $end = Carbon::parse(
                $data['end_at'],
                'America/Bogota'
            );

            // Validar que la fecha de inicio no esté en el pasado.
            if ($start->lessThan(now('America/Bogota'))) {
                throw ValidationException::withMessages([
                    'start_at' => 'La reserva no puede comenzar en el pasado.',
                ]);
            }

            // Validar máximo de 7 días de anticipación.
            if ($start->greaterThan(
                now('America/Bogota')->addDays(7)
            )) {
                throw ValidationException::withMessages([
                    'start_at' => 'Solo puedes reservar con 7 días de anticipación.',
                ]);
            }

            // Validar que la fecha final sea posterior a la inicial.
            if ($end->lessThanOrEqualTo($start)) {
                throw ValidationException::withMessages([
                    'end_at' => 'La fecha final debe ser posterior al inicio.',
                ]);
            }

            // Validar duración entre 1 y 12 horas.
            $duration = $start->diffInMinutes($end);

            if ($duration < 60 || $duration > 720) {
                throw ValidationException::withMessages([
                    'end_at' => 'La reserva debe durar entre 1 y 12 horas.',
                ]);
            }

            // Determinar día operativo y turno.
            $hour = (int) $start->format('H');

            if ($hour >= 6 && $hour < 18) {

                $shiftType = 'day';

                $operationalDate = $start->toDateString();

                $shiftStart = $start->copy()->setTime(6, 0);

                $shiftEnd = $start->copy()->setTime(18, 0);

            } else {

                $shiftType = 'night';

                // Las reservas de 00:00 a 05:59
                // pertenecen al turno nocturno anterior.
                $operationalDate = $hour < 6
                    ? $start->copy()->subDay()->toDateString()
                    : $start->toDateString();

                $shiftStart = $start->copy()->setTime(18, 0);

                if ($hour < 6) {
                    $shiftStart->subDay();
                }

                $shiftEnd = $shiftStart->copy()
                    ->addDay()
                    ->setTime(6, 0);
            }

            // Validar límites del turno.
            if (
                $start->lessThan($shiftStart) ||
                $end->greaterThan($shiftEnd)
            ) {
                throw ValidationException::withMessages([
                    'start_at' => 'La reserva debe estar dentro del turno seleccionado.',
                ]);
            }

            // Una reserva por día operativo.
            $existingUserReservation = Reservation::where(
                'user_id',
                $user->id
            )
                ->where('operational_date', $operationalDate)
                ->where('status', '!=', 'cancelled')
                ->exists();

            if ($existingUserReservation) {
                throw ValidationException::withMessages([
                    'start_at' => 'Ya tienes una reserva para este día operativo.',
                ]);
            }

            // Verificar disponibilidad del cargador.
            // Se excluyen las reservas canceladas.
            $chargerOccupied = Reservation::where(
                'charger_id',
                $charger->id
            )
                ->where('status', '!=', 'cancelled')
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start)
                ->exists();

            if ($chargerOccupied) {
                throw ValidationException::withMessages([
                    'charger_id' => 'El cargador ya está reservado en ese horario.',
                ]);
            }

            // Crear reserva.
            return Reservation::create([
                'user_id' => $user->id,
                'charger_id' => $charger->id,
                'operational_date' => $operationalDate,
                'shift_type' => $shiftType,
                'start_at' => $start,
                'end_at' => $end,
                'status' => 'scheduled',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
