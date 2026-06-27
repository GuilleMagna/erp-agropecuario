<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->string('nombre', 100);
            $table->string('tipo', 30)->default('banco');
            $table->string('moneda', 10)->default('ARS');
            $table->string('numero_cuenta', 50)->nullable();
            $table->string('banco', 100)->nullable();
            $table->decimal('saldo_inicial', 14, 2)->default(0);
            $table->boolean('activa')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->index('id_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
