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
     * Vincula las ventas al catálogo de compradores. Se mantiene la columna
     * "comprador" (texto) en ambas tablas para no romper reportes existentes
     * y para conservar el nombre tal cual estaba en ventas ya cargadas antes
     * de este catálogo (id_comprador null en esos casos).
     */
    public function up(): void
    {
        Schema::table('ventas_granos', function (Blueprint $table) {
            $table->foreignUuid('id_comprador')->nullable()->after('comprador')
                ->constrained('compradores')->nullOnDelete();
        });

        Schema::table('ventas_hacienda', function (Blueprint $table) {
            $table->foreignUuid('id_comprador')->nullable()->after('comprador')
                ->constrained('compradores')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas_granos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_comprador');
        });

        Schema::table('ventas_hacienda', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_comprador');
        });
    }
};
