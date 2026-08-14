<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos fiscales de la liquidación de hacienda.
     *
     * Las ventas de hacienda no guardaban ni el número del comprobante ni el
     * débito fiscal, así que no había forma de cotejarlas contra el Libro IVA
     * Ventas, que las informa como Liquida, LIQ DE y Cta.de. Es el mismo par
     * de campos que ya tienen las ventas de granos.
     */
    public function up(): void
    {
        Schema::table('ventas_hacienda', function (Blueprint $table) {
            $table->string('numero_comprobante', 50)->nullable()->after('numero_guia');
            $table->decimal('debito_fiscal', 14, 2)->default(0)->after('importe_total');
        });
    }

    public function down(): void
    {
        Schema::table('ventas_hacienda', function (Blueprint $table) {
            $table->dropColumn(['numero_comprobante', 'debito_fiscal']);
        });
    }
};
