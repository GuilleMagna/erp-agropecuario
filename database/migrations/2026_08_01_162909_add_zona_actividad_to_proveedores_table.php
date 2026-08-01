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
     * Clasificación por defecto del proveedor (de dónde suele venir el gasto),
     * usada para autocompletar actividad/zona/rubro en cada compra nueva que
     * se le impute, igual que hacía el VLOOKUP por CUIT del Excel de origen.
     */
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('actividad', 30)->nullable()->after('rubro');
            $table->string('zona', 30)->nullable()->after('actividad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['actividad', 'zona']);
        });
    }
};
