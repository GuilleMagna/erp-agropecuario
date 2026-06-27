<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_fiscales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('periodo', 7)->unique()->comment('Formato YYYY-MM, ej: 2026-06');
            $table->enum('estado', ['abierto', 'cerrado', 'presentado'])->default('abierto');
            $table->date('fecha_cierre')->nullable();
            $table->date('fecha_presentacion')->nullable();
            $table->string('numero_formulario', 50)->nullable()->comment('Número de DDJJ');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_fiscales');
    }
};
