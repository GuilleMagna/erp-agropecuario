<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->string('nombre', 150);
            $table->string('codigo', 30)->nullable();
            $table->string('tipo', 50)->default('otro');
            $table->string('unidad', 20)->default('unidad');
            $table->string('marca', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('stock_minimo', 10, 2)->nullable();
            $table->decimal('precio_referencia', 12, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->index('id_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
