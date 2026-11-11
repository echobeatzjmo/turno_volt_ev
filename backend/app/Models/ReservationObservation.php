<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationObservation extends Model
{
    protected $fillable = [
        'reservation_id',
        'user_id',
        'observation',
    ];

    /**
     * Reserva relacionada con la observación.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Usuario que registró la observación.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
