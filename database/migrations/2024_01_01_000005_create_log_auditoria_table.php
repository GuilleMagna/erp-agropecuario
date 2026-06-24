<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documento 03, sección 4.9 — Tabla log_auditoria
 * Registro inmutable de toda acción de creación, modificación o eliminación
 * sobre datos relevantes del sistema. Requerimiento funcional RF-004 y no
 * funcional RNF-007 (Documento 09).
 *
 * NOTA sobre spatie/laravel-activitylog: este paquete también genera su propia
 * tabla (activity_log), que resuelve buena parte de este requerimiento de forma
 * automática usando Observers de Eloquent. Lo que se crea acá es la tabla propia
 * del proyecto para casos donde se necesite un registro adicional fuera de los
 * que ActivityLog captura por defecto (por ejemplo, intentos de acceso fallidos,
 * acciones masivas de importación Excel). Ambas tablas conviven sin conflicto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_auditoria', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->string('accion', 20)
                ->comment('CREATE / UPDATE / DELETE');
            $table->string('entidad', 100)
                ->comment('nombre de la tabla afectada (ej. "animales")');
            $table->uuid('id_entidad')
                ->comment('id del registro afectado');

            // JSONB guarda el snapshot previo y el nuevo, sin FK estricta hacia
            // la entidad afectada porque el registro de auditoría debe sobrevivir
            // incluso si el registro original se elimina lógicamente.
            // (Documento 03, sección 4.9 — nota de diseño)
            $table->jsonb('valores_anteriores')->nullable();
            $table->jsonb('valores_nuevos')->nullable();

            $table->string('ip_origen', 50)->nullable();
            $table->string('dispositivo', 100)->nullable()
                ->comment('web / movil / artisan');

            // Sin updated_at: el log es inmutable, nunca se edita.
            $table->timestamp('created_at');

            // Índices para las consultas más frecuentes del log:
            // "¿qué hizo este usuario?" y "¿quién tocó este registro?"
            $table->index('id_usuario');
            $table->index('id_entidad');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_auditoria');
    }
};
