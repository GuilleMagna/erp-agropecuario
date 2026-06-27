<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_compra');
            $table->uuid('id_insumo')->nullable();
            $table->string('descripcion', 200);
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad', 20)->nullable();
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('id_compra')->references('id')->on('compras')->onDelete('cascade');
            $table->foreign('id_insumo')->references('id')->on('insumos')->nullOnDelete();

            $table->index('id_compra');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_items');
    }
};
