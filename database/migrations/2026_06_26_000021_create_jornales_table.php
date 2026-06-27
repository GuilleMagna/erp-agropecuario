<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jornales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_empleado')->nullable();
            $table->uuid('id_establecimiento')->nullable();
            $table->date('fecha');
            $table->string('tipo_jornada', 20)->default('completa');
            $table->decimal('horas_trabajadas', 5, 2)->nullable();
            $table->string('tarea', 200)->nullable();
            $table->decimal('importe', 12, 2)->default(0);
            $table->string('estado', 20)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('id_empleado')->references('id')->on('empleados')->nullOnDelete();
            $table->foreign('id_establecimiento')->references('id')->on('establecimientos')->nullOnDelete();

            $table->index(['id_empresa', 'fecha']);
            $table->index(['id_empresa', 'id_empleado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornales');
    }
};
