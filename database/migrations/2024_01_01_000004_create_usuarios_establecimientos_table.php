<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documento 03, sección 4.8 — Tabla usuarios_establecimientos (N:M)
 * Restringe el acceso de cada usuario a los establecimientos que le fueron
 * asignados explícitamente. Un capataz del campo "La Esperanza" no debe poder
 * ver datos del "El Retiro" salvo que se le habilite (RNF-008, Documento 09).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_establecimientos', function (Blueprint $table) {
            $table->foreignUuid('id_usuario')
                ->constrained('usuarios')
                ->cascadeOnDelete();
            $table->foreignUuid('id_establecimiento')
                ->constrained('establecimientos')
                ->cascadeOnDelete();

            $table->primary(['id_usuario', 'id_establecimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_establecimientos');
    }
};
