<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Obtener el usuario autenticado.
        $user = $request->user();

        // Verificar que tenga permisos para consultar la auditoría.
        if (!in_array($user->role, [
            'administrador',
            'administrador_avanzado',
        ])) {
            return response()->json([
                'message' => 'No tienes permisos para consultar los registros de auditoría.',
            ], 403);
        }

        // Consultar los registros más recientes.
        $auditLogs = AuditLog::with([
            'user',
            'auditable',
        ])
            ->latest()
            ->paginate(15);

        return response()->json([
            'message' => 'Registros de auditoría consultados correctamente.',
            'data' => $auditLogs,
        ], 200);
    }
}
