<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_establecimiento')->nullable();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('dni', 20)->nullable();
            $table->string('cuil', 20)->nullable();
            $table->string('tipo_contrato', 30)->default('jornal');
            $table->string('categoria', 60)->nullable();
            $table->date('fecha_ingreso');
            $table->date('fecha_egreso')->nullable();
            $table->decimal('sueldo_base', 12, 2)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('cbu', 30)->nullable();
            $table->string('banco', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('id_establecimiento')->references('id')->on('establecimientos')->nullOnDelete();

            $table->index(['id_empresa', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
