<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumos_alimento', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_corral')->nullable()->constrained('corrales')->nullOnDelete();
            $table->foreignUuid('id_tropa')->nullable()->constrained('tropas')->nullOnDelete();
            $table->foreignUuid('id_establecimiento')->nullable()->constrained('establecimientos')->nullOnDelete();
            $table->foreignUuid('id_insumo')->nullable()->constrained('insumos')->nullOnDelete();
            $table->date('fecha');
            $table->string('descripcion_alimento', 150)->nullable();
            $table->decimal('cantidad_kg', 10, 2);
            $table->decimal('costo_unitario', 10, 4)->nullable();
            $table->decimal('costo_total', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['id_empresa', 'fecha']);
            $table->index(['id_empresa', 'id_corral']);
            $table->index(['id_empresa', 'id_tropa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumos_alimento');
    }
};
