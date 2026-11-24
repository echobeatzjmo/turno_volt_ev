<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Consultar los horarios disponibles de un cargador.
     */
    public function index(Request $request)
    {
        // Validar los parámetros recibidos.
        $validated = $request->validate([
            'charger_id' => ['required', 'integer', 'min:1'],
            'operational_date' => ['required', 'date_format:Y-m-d'],
            'shift_type' => ['required', 'in:day,night'],
        ]);

        // Consultar disponibilidad mediante el servicio.
        $availability = $this->availabilityService->getAvailableSlots(
            $validated['charger_id'],
            $validated['operational_date'],
            $validated['shift_type']
        );

        return response()->json([
            'message' => 'Disponibilidad consultada correctamente.',
            'data' => $availability,
        ], 200);
    }
}
