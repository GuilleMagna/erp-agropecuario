<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca si el comprobante está incluido en lo que el contador presentó
     * ante ARCA. Permite que el total "presentado" del ERP coincida con el
     * libro IVA sin tener que borrar los comprobantes que quedaron afuera.
     *
     * Arranca en true para todo lo ya cargado: lo que quedó fuera de una
     * presentación se marca a mano (o por migración de datos, como el caso
     * de ELVIO enero 2026).
     */
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->boolean('presentado_arca')->default(true)->after('stock_registrado');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn('presentado_arca');
        });
    }
};
