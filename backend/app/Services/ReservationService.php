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

    public function update(User $user, Reservation $reservation, array $data): Reservation 
    {
        return DB::transaction(function () use ($user, $reservation, $data) {

        // Bloquear al usuario para evitar modificaciones simultáneas.
        User::whereKey($user->id)
            ->lockForUpdate()
            ->firstOrFail();

        // Bloquear la reserva que se va a modificar.
        $reservation = Reservation::whereKey($reservation->id)
            ->lockForUpdate()
            ->firstOrFail();

        // No se permite editar una reserva que ya comenzó.
        if (
            Carbon::parse($reservation->start_at)
                ->lessThanOrEqualTo(now('America/Bogota'))
        ) {
            throw ValidationException::withMessages([
                'reservation' => 'No puedes modificar una reserva que ya comenzó.',
            ]);
        }

        // Bloquear el cargador solicitado.
        $charger = Charger::whereKey($data['charger_id'])
            ->lockForUpdate()
            ->firstOrFail();

        // Verificar que el cargador esté activo.
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
        if (
            $start->greaterThan(
                now('America/Bogota')->addDays(7)
            )
        ) {
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

        // Validar una reserva por día operativo.
        // Se excluye la reserva que estamos editando.
        $existingUserReservation = Reservation::where(
            'user_id',
            $user->id
        )
            ->where('operational_date', $operationalDate)
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $reservation->id)
            ->exists();

        if ($existingUserReservation) {
            throw ValidationException::withMessages([
                'start_at' => 'Ya tienes una reserva para este día operativo.',
            ]);
        }

        // Verificar disponibilidad del cargador.
        // Se excluyen reservas canceladas y la reserva actual.
        $chargerOccupied = Reservation::where(
            'charger_id',
            $charger->id
        )
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $reservation->id)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();

        if ($chargerOccupied) {
            throw ValidationException::withMessages([
                'charger_id' => 'El cargador ya está reservado en ese horario.',
            ]);
        }

        // Actualizar los datos de la reserva.
        $reservation->update([
            'charger_id' => $charger->id,
            'operational_date' => $operationalDate,
            'shift_type' => $shiftType,
            'start_at' => $start,
            'end_at' => $end,
            'notes' => $data['notes'] ?? null,
        ]);

        return $reservation->fresh(['user', 'charger']);
    });
    }

    public function cancel(User $user, Reservation $reservation): Reservation {
    return DB::transaction(function () use ($user, $reservation) {

        // Bloquear al usuario para evitar operaciones simultáneas.
        User::whereKey($user->id)
            ->lockForUpdate()
            ->firstOrFail();

        // Bloquear la reserva que se va a cancelar.
        $reservation = Reservation::whereKey($reservation->id)
            ->lockForUpdate()
            ->firstOrFail();

        // Verificar que la reserva todavía esté programada.
        if ($reservation->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'reservation' => 'Solo puedes cancelar reservas que estén programadas.',
            ]);
        }

        // Obtener la fecha de inicio en la zona horaria de Colombia.
        $start = Carbon::parse(
            $reservation->start_at
        )->setTimezone('America/Bogota');

        $now = now('America/Bogota');

        // No permitir cancelar una reserva que ya comenzó.
        if ($start->lessThanOrEqualTo($now)) {
            throw ValidationException::withMessages([
                'reservation' => 'No puedes cancelar una reserva que ya comenzó.',
            ]);
        }

        // No permitir cancelar cuando faltan 30 minutos o menos.
        $minutesUntilStart = $now->diffInMinutes($start);

        if ($minutesUntilStart <= 30) {
            throw ValidationException::withMessages([
                'reservation' => 'No puedes cancelar una reserva cuando faltan 30 minutos o menos para su inicio.',
            ]);
        }

        // Cambiar el estado para conservar el historial.
        $reservation->update([
            'status' => 'cancelled',
        ]);

        return $reservation->fresh(['user', 'charger']);
    });
}
}
