<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Registrar una operación del sistema.
     */
    public function log(
        string $action,
        ?Model $auditable = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): AuditLog {

        // Identificar al usuario responsable.
        $user = $user ?? Auth::user();

        // Registrar la información de auditoría.
        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,

            'auditable_type' => $auditable
                ? get_class($auditable)
                : 'system',

            'auditable_id' => $auditable?->getKey() ?? 0,

            'description' => $description,

            'old_values' => $oldValues,
            'new_values' => $newValues,

            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
