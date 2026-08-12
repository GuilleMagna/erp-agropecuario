<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos fiscales de la liquidación primaria de granos.
     *
     * Hasta ahora las ventas de granos no guardaban ni el número de la
     * liquidación ni el débito fiscal, así que no había forma de cotejarlas
     * contra el Libro IVA Ventas y el reporte fiscal tenía que estimar el IVA.
     *
     * numero_comprobante ya existía en la tabla pero estaba sin usar; acá se
     * agrega sólo el débito fiscal.
     */
    public function up(): void
    {
        Schema::table('ventas_granos', function (Blueprint $table) {
            $table->decimal('debito_fiscal', 14, 2)->default(0)->after('iva_rg4310');
        });
    }

    public function down(): void
    {
        Schema::table('ventas_granos', function (Blueprint $table) {
            $table->dropColumn('debito_fiscal');
        });
    }
};
