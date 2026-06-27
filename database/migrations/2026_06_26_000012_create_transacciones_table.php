<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_cuenta');
            $table->string('tipo', 10);
            $table->string('categoria', 60);
            $table->string('concepto', 200);
            $table->decimal('importe', 14, 2);
            $table->date('fecha');
            $table->uuid('id_establecimiento')->nullable();
            $table->string('numero_comprobante', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('id_cuenta')->references('id')->on('cuentas')->onDelete('cascade');
            $table->foreign('id_establecimiento')->references('id')->on('establecimientos')->nullOnDelete();

            $table->index('id_empresa');
            $table->index('id_cuenta');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones');
    }
};
