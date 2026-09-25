<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Charger extends Model
{
    protected $fillable = [
        'number',
        'name',
        'is_active',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}