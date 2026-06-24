<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documento 03, sección 4.3 — Tabla usuarios
 * Cada persona que usa el sistema tiene un usuario individual (nunca
 * compartido), base de la autenticación y de la autoría de cada acción
 * registrada en el log de auditoría (Documento 02, sección 1.1 y 1.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();

            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('email', 150)->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('password');
            $table->string('foto_url', 300)->nullable();
            $table->boolean('mfa_habilitado')->default(false);

            // Nunca se borra un usuario, se inactiva (Documento 03, criterios
            // de diseño, sección 1), para no perder la trazabilidad histórica
            // de lo que esa persona registró.
            $table->boolean('activo')->default(true);

            $table->timestamp('ultimo_acceso')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        // Ahora que usuarios existe, se completa la FK que quedó pendiente
        // en la migración de establecimientos (Documento 03, sección 4.2).
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->foreign('responsable_id')
                ->references('id')->on('usuarios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
        });

        Schema::dropIfExists('usuarios');
    }
};
