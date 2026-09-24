<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('charger_id')
                ->constrained('chargers')
                ->restrictOnDelete();

            /*
             * Fecha del día operativo.
             *
             * Para un turno nocturno:
             * 23/09 22:00 -> 24/09 02:00
             *
             * operational_date = 23/09
             */
            $table->date('operational_date');

            $table->enum('shift_type', [
                'day',
                'night',
            ]);

            /*
             * Se almacenan como fecha + hora.
             * PostgreSQL manejará estos valores con zona horaria.
             */
            $table->timestampTz('start_at');

            $table->timestampTz('end_at');

            $table->enum('status', [
                'scheduled',
                'active',
                'completed',
                'cancelled',
                'no_show',
            ])->default('scheduled');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index([
                'operational_date',
                'shift_type',
            ]);

            $table->index([
                'charger_id',
                'start_at',
                'end_at',
            ]);

            /*
             * Un usuario no puede tener más de una reserva
             * durante el mismo día operativo.
             */
            $table->unique([
                'user_id',
                'operational_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};