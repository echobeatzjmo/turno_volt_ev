<?php

namespace App\Services;

use App\Models\Charger;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    /**
     * Consultar los intervalos disponibles de un cargador.
     */
    public function getAvailableSlots(
        int $chargerId,
        string $operationalDate,
        string $shiftType
    ): array {

        // Validar el turno solicitado.
        if (!in_array($shiftType, ['day', 'night'])) {
            throw ValidationException::withMessages([
                'shift_type' => 'El turno debe ser day o night.',
            ]);
        }

        // Buscar el cargador y verificar que esté activo.
        $charger = Charger::where('is_active', true)
            ->find($chargerId);

        if (!$charger) {
            throw ValidationException::withMessages([
                'charger_id' => 'El cargador no existe o no está activo.',
            ]);
        }

        // Definir los límites del turno.
        $shiftStart = Carbon::parse(
            $operationalDate,
            'America/Bogota'
        );

        if ($shiftType === 'day') {
            $shiftStart->setTime(6, 0);
            $shiftEnd = $shiftStart->copy()->setTime(18, 0);
        } else {
            $shiftStart->setTime(18, 0);
            $shiftEnd = $shiftStart->copy()
                ->addDay()
                ->setTime(6, 0);
        }

        // Consultar reservas que se cruzan con el turno.
        $reservations = $charger->reservations()
            ->where('status', '!=', 'cancelled')
            ->where('start_at', '<', $shiftEnd)
            ->where('end_at', '>', $shiftStart)
            ->orderBy('start_at')
            ->get(['start_at', 'end_at']);

        // Calcular intervalos disponibles.
        $availableSlots = [];
        $currentStart = $shiftStart->copy();

        foreach ($reservations as $reservation) {

            $reservationStart = Carbon::parse(
                $reservation->start_at
            )->setTimezone('America/Bogota');

            $reservationEnd = Carbon::parse(
                $reservation->end_at
            )->setTimezone('America/Bogota');

            if ($reservationStart->greaterThan($currentStart)) {
                $availableSlots[] = [
                    'start_at' => $currentStart->toDateTimeString(),
                    'end_at' => $reservationStart->toDateTimeString(),
                ];
            }

            if ($reservationEnd->greaterThan($currentStart)) {
                $currentStart = $reservationEnd->copy();
            }
        }

        // Agregar el espacio libre restante.
        if ($currentStart->lessThan($shiftEnd)) {
            $availableSlots[] = [
                'start_at' => $currentStart->toDateTimeString(),
                'end_at' => $shiftEnd->toDateTimeString(),
            ];
        }

        return [
            'charger' => [
                'id' => $charger->id,
                'number' => $charger->number,
                'name' => $charger->name,
            ],
            'operational_date' => $operationalDate,
            'shift_type' => $shiftType,
            'available_slots' => $availableSlots,
        ];
    }
}
