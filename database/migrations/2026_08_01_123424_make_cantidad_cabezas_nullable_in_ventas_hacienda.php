<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ventas_hacienda', function (Blueprint $table) {
            // Hay ventas a frigorífico donde solo se factura por KG, sin cantidad de
            // cabezas conocida (ver peso_total_kg, ya nullable, para ese caso).
            $table->unsignedInteger('cantidad_cabezas')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas_hacienda', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_cabezas')->nullable(false)->change();
        });
    }
};
