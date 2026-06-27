<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cosechas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_siembra')->constrained('siembras')->cascadeOnDelete();

            $table->date('fecha_cosecha');
            $table->decimal('superficie_cosechada_ha', 10, 2);
            $table->decimal('rinde_kg_ha', 10, 2);
            $table->decimal('humedad_porc', 4, 1)->nullable();
            $table->decimal('produccion_total_kg', 14, 2)->nullable()
                ->comment('superficie × rinde, almacenado para performance en reportes');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->index('id_empresa');
            $table->index('id_siembra');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosechas');
    }
};
