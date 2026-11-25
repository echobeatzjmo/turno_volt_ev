<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Usuario que ejecutó la acción.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Acción realizada.
            $table->string('action', 100);

            // Entidad afectada (Reserva, Usuario, Cargador, etc.).
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');

            // Descripción de la operación.
            $table->text('description')->nullable();

            // Valores anteriores y nuevos.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Información de la solicitud.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Índice para consultar los registros de una entidad.
            $table->index(
                ['auditable_type', 'auditable_id'],
                'audit_logs_auditable_index'
            );

            // Índice para consultar las acciones de un usuario.
            $table->index(
                ['user_id', 'created_at'],
                'audit_logs_user_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

