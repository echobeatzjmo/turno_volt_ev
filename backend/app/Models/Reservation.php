<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'charger_id',
        'operational_date',
        'shift_type',
        'start_at',
        'end_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'operational_date' => 'date',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function charger(): BelongsTo
    {
        return $this->belongsTo(Charger::class);
    }
}