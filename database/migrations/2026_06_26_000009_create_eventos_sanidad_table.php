<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_sanidad', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_establecimiento')->constrained('establecimientos')->cascadeOnDelete();
            $table->foreignUuid('id_animal')->nullable()
                ->constrained('animales')->nullOnDelete()
                ->comment('Null = evento grupal por categoría');

            $table->string('tipo_evento', 30)
                ->comment('vacunacion/desparasitacion/tratamiento/diagnostico/castracion/otro');
            $table->date('fecha');
            $table->string('producto', 100)->nullable()->comment('Vacuna, medicamento o producto utilizado');
            $table->string('dosis', 50)->nullable();
            $table->string('veterinario', 100)->nullable();
            $table->string('categoria_afectada', 20)->nullable()->comment('Para eventos grupales');
            $table->integer('cantidad_afectada')->nullable()->comment('Cabezas tratadas en evento grupal');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->index('id_empresa');
            $table->index('id_establecimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_sanidad');
    }
};
