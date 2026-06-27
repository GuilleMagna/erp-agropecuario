<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_insumos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_insumo');
            $table->uuid('id_establecimiento')->nullable();
            $table->string('tipo', 20);
            $table->string('motivo', 60);
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->decimal('importe_total', 14, 2)->nullable();
            $table->date('fecha');
            $table->string('numero_remito', 50)->nullable();
            $table->string('proveedor', 150)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('id_insumo')->references('id')->on('insumos')->onDelete('cascade');
            $table->foreign('id_establecimiento')->references('id')->on('establecimientos')->nullOnDelete();

            $table->index('id_empresa');
            $table->index('id_insumo');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_insumos');
    }
};
