<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tropas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_corral')->nullable()->constrained('corrales')->nullOnDelete();
            $table->foreignUuid('id_establecimiento')->nullable()->constrained('establecimientos')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('categoria', 30);
            $table->unsignedInteger('cantidad_cabezas');
            $table->date('fecha_entrada');
            $table->date('fecha_salida_estimada')->nullable();
            $table->date('fecha_salida_real')->nullable();
            $table->decimal('peso_promedio_entrada_kg', 8, 2)->nullable();
            $table->decimal('peso_promedio_salida_kg', 8, 2)->nullable();
            $table->decimal('objetivo_ganancia_diaria_kg', 5, 3)->nullable();
            $table->string('estado', 20)->default('activa');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['id_empresa', 'estado']);
            $table->index(['id_empresa', 'id_corral']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tropas');
    }
};
