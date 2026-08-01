<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Categorización gestionable de qué se vende (Vacas, Cereales, ...). Define
     * qué campo de cantidad pide el formulario de ventas: animales_kg (cantidad
     * de cabezas + KG, hacienda) o quintales (granos, 1 quintal = 100kg).
     */
    public function up(): void
    {
        Schema::create('categorias_venta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 100);
            $table->string('tipo_cantidad', 20)->default('animales_kg')
                ->comment('animales_kg / quintales');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias_venta');
    }
};
