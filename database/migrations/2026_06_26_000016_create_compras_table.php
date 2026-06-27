<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_proveedor')->nullable();
            $table->uuid('id_establecimiento')->nullable();
            $table->string('tipo_comprobante', 30)->default('factura_b');
            $table->string('numero_comprobante', 50)->nullable();
            $table->date('fecha');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado', 20)->default('recibida');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('iva_porc', 5, 2)->nullable();
            $table->decimal('iva_importe', 14, 2)->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->boolean('stock_registrado')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('id_proveedor')->references('id')->on('proveedores')->nullOnDelete();
            $table->foreign('id_establecimiento')->references('id')->on('establecimientos')->nullOnDelete();

            $table->index('id_empresa');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
