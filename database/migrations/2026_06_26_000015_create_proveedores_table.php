<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->string('nombre', 150);
            $table->string('razon_social', 200)->nullable();
            $table->string('cuit', 20)->nullable();
            $table->string('rubro', 60)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('provincia', 80)->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->index('id_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
