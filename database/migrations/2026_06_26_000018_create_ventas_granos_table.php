<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_granos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_establecimiento')->nullable();
            $table->uuid('id_campana')->nullable();
            $table->string('comprador', 200)->nullable();
            $table->string('cuit_comprador', 20)->nullable();
            $table->string('cereal', 30);
            $table->string('tipo_venta', 30)->default('disponible');
            $table->string('corredor', 150)->nullable();
            $table->string('numero_comprobante', 50)->nullable();
            $table->date('fecha');
            $table->date('fecha_entrega')->nullable();
            $table->decimal('cantidad_tn', 12, 3);
            $table->decimal('precio_tn', 12, 2);
            $table->string('moneda', 3)->default('USD');
            $table->decimal('importe_total', 14, 2);
            $table->string('estado', 20)->default('confirmada');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('id_establecimiento')->references('id')->on('establecimientos')->nullOnDelete();
            $table->foreign('id_campana')->references('id')->on('campanas')->nullOnDelete();

            $table->index(['id_empresa', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_granos');
    }
};
