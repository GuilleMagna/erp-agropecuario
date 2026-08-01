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
     * La zona no es una propiedad del proveedor: pasa a vivir en
     * Establecimiento (ver add_zona_to_establecimientos_table). Los valores
     * ya cargados en las compras de 2024 (columna compras.zona) no se
     * pierden, esta columna solo afectaba al proveedor.
     */
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('zona');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('zona', 30)->nullable()->after('actividad');
        });
    }
};
