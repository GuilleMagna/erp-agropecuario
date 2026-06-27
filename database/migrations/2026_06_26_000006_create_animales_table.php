<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_establecimiento')->constrained('establecimientos')->cascadeOnDelete();

            $table->string('caravana', 30)->nullable()->comment('Número de caravana / carimbo');
            $table->string('raza', 50)->nullable();
            $table->string('sexo', 10)->default('macho')->comment('macho / hembra');
            $table->string('categoria', 20)->comment('vaca/toro/novillo/vaquillona/ternero/ternera/torito/buey');
            $table->date('fecha_nacimiento')->nullable();
            $table->date('fecha_ingreso');
            $table->decimal('peso_ingreso_kg', 8, 2)->nullable();
            $table->decimal('peso_actual_kg', 8, 2)->nullable()->comment('Actualizado al registrar pesajes');
            $table->string('color', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->index('id_empresa');
            $table->index('id_establecimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animales');
    }
};
