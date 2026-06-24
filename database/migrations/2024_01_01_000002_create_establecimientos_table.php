<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documento 03, sección 4.2 — Tabla establecimientos
 * Representa cada campo físico de la empresa. Punto de anclaje usado en
 * casi todas las consultas y reportes del sistema (filtro por campo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimientos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();

            $table->string('nombre', 150)->comment('ej. "La Esperanza"');
            $table->string('provincia', 100)->nullable();
            $table->string('partido_departamento', 100)->nullable();
            $table->string('localidad', 100)->nullable();
            $table->decimal('latitud', 10, 6)->nullable();
            $table->decimal('longitud', 10, 6)->nullable();

            $table->decimal('superficie_total_ha', 10, 2)->nullable();
            // Superficies agrícola/ganadera cacheadas por performance de
            // dashboards (Documento 03, nota de diseño sección 4.2), aunque
            // sean técnicamente calculables a partir de unidades_manejo.
            $table->decimal('superficie_agricola_ha', 10, 2)->nullable();
            $table->decimal('superficie_ganadera_ha', 10, 2)->nullable();

            $table->string('tipo_tenencia', 20)->default('propio')
                ->comment('propio / arrendado / sociedad');
            $table->string('partida_catastral', 100)->nullable();

            $table->foreignUuid('responsable_id')->nullable()
                ->comment('FK a usuarios, encargado del campo. Se agrega la constraint en la migración de usuarios para evitar dependencia circular.');

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('id_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};
