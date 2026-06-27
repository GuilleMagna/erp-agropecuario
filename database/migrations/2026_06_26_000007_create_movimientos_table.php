<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_establecimiento')->constrained('establecimientos')->cascadeOnDelete();

            $table->string('tipo', 30)
                ->comment('compra/venta/nacimiento/muerte/faena/transferencia_entrada/transferencia_salida');
            $table->date('fecha');
            $table->string('categoria', 20)->comment('categoria de los animales del movimiento');
            $table->integer('cantidad');
            $table->decimal('peso_total_kg', 10, 2)->nullable();
            $table->decimal('precio_cabeza', 10, 2)->nullable();
            $table->decimal('importe_total', 12, 2)->nullable();
            $table->string('procedencia_destino', 150)->nullable()
                ->comment('Nombre del vendedor, comprador u establecimiento de origen/destino');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->index('id_empresa');
            $table->index('id_establecimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
