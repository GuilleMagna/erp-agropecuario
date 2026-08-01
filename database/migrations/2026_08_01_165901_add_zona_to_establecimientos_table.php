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
     * La zona (región geográfica del campo) vive acá, no en Proveedor ni en
     * Compra: es una propiedad del establecimiento. Compra.zona (columna ya
     * existente) se autocompleta desde acá al elegir el establecimiento de
     * la compra, en vez de venir del proveedor.
     */
    public function up(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->string('zona', 30)->nullable()->after('localidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->dropColumn('zona');
        });
    }
};
