<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_hacienda', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_establecimiento')->nullable();
            $table->string('comprador', 200)->nullable();
            $table->string('corredor_feria', 150)->nullable();
            $table->string('numero_guia', 50)->nullable();
            $table->date('fecha');
            $table->string('tipo_operacion', 30)->default('terminado');
            $table->string('categoria', 30);
            $table->unsignedInteger('cantidad_cabezas');
            $table->decimal('peso_promedio_kg', 8, 2)->nullable();
            $table->decimal('peso_total_kg', 10, 2)->nullable();
            $table->decimal('precio_kg', 10, 4)->nullable();
            $table->decimal('precio_cabeza', 12, 2)->nullable();
            $table->decimal('importe_total', 14, 2);
            $table->string('moneda', 3)->default('ARS');
            $table->string('estado', 20)->default('confirmada');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('id_establecimiento')->references('id')->on('establecimientos')->nullOnDelete();

            $table->index(['id_empresa', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_hacienda');
    }
};
