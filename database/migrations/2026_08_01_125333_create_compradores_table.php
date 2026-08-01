<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Catálogo de compradores/corredores. Global (no por empresa): el mismo
     * comprador suele operar con ELVIO, WILMAR y SOCIEDAD indistintamente.
     */
    public function up(): void
    {
        Schema::create('compradores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 200);
            $table->string('cuit', 20)->nullable();
            $table->foreignUuid('id_categoria_venta')->nullable()
                ->constrained('categorias_venta')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compradores');
    }
};
