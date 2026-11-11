<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservation_observations', function (Blueprint $table) {
            $table->id();

            // Reserva a la que pertenece la observación.
            $table->foreignId('reservation_id')
                ->constrained('reservations')
                ->cascadeOnDelete();

            // Usuario que registra la observación.
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Contenido de la observación.
            $table->text('observation');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_observations');
    }
};
