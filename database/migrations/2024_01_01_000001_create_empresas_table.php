<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documento 03, sección 4.1 — Tabla empresas
 * Raíz de toda la jerarquía multiempresa del sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('razon_social', 200);
            $table->string('cuit', 13)->unique();
            $table->string('condicion_fiscal', 50)
                ->comment('Responsable Inscripto, Monotributo, etc.');
            $table->string('domicilio_fiscal', 300)->nullable();
            $table->string('logo_url', 300)->nullable();
            $table->string('moneda_default', 3)->default('ARS');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
