<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva observación - Turno Volt EV</title>
</head>
<body style="margin:0; padding:30px; background-color:#f3f4f6; font-family:Arial,sans-serif; color:#333;">

    <div style="max-width:650px; margin:auto; background:#ffffff; border-radius:10px; overflow:hidden;">

        <div style="background:#153e35; padding:25px; color:#ffffff;">
            <h1 style="margin:0;">Turno Volt EV</h1>
            <p style="margin:8px 0 0;">Notificación de observación</p>
        </div>

        <div style="padding:30px;">

            <h2 style="color:#153e35;">Nueva observación registrada</h2>

            <p>
                Se ha registrado una nueva observación relacionada
                con una reserva de cargador eléctrico.
            </p>

            <h3>Información del residente</h3>
            <p>
                <strong>Nombre:</strong>
                {{ $observation->reservation->user->name }}
            </p>

            <h3>Información de la reserva</h3>
            <p>
                <strong>Cargador:</strong>
                {{ $observation->reservation->charger->name }}
            </p>
            <p>
                <strong>Fecha:</strong>
                {{ $observation->reservation->operational_date->format('d/m/Y') }}
            </p>
            <p>
                <strong>Inicio:</strong>
                {{ $observation->reservation->start_at->timezone('America/Bogota')->format('d/m/Y H:i') }}
            </p>
            <p>
                <strong>Finalización:</strong>
                {{ $observation->reservation->end_at->timezone('America/Bogota')->format('d/m/Y H:i') }}
            </p>

            <h3>Observación</h3>

            <div style="background:#f3f4f6; padding:18px; border-left:4px solid #198754; border-radius:5px;">
                {{ $observation->observation }}
            </div>

            <h3>Responsable del registro</h3>
            <p>
                <strong>Operador:</strong>
                {{ $observation->user->name }}
            </p>

        </div>

        <div style="background:#eeeeee; padding:18px; text-align:center; font-size:12px; color:#666;">
            Este mensaje fue generado automáticamente por Turno Volt EV.
        </div>

    </div>

</body>
</html>
