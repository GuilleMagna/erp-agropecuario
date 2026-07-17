<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingresos_alquiler', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_inmueble');
            $table->foreign('id_inmueble')->references('id')->on('inmuebles')->cascadeOnDelete();

            $table->date('mes')->comment('Primer día del mes: 2026-01-01');
            $table->decimal('importe', 14, 2)->default(0);
            $table->date('fecha_cobro')->nullable();
            $table->string('notas', 300)->nullable();
            $table->timestamps();
            $table->unique(['id_inmueble', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos_alquiler');
    }
};
