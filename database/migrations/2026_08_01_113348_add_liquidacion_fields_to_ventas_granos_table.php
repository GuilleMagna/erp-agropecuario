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
        Schema::table('ventas_granos', function (Blueprint $table) {
            // Réplica de las columnas de entrada de la hoja VENTAS del Excel de control
            // mensual (CONTROL MENSUAL 2026.xlsx). Nullable y sin default de negocio: los
            // registros existentes (importados o cargados antes de este cambio) no tienen
            // esta información desagregada, solo el importe_total ya calculado.
            $table->decimal('cantidad_kg', 12, 2)->nullable()->after('cantidad_tn');
            $table->decimal('factor', 6, 2)->nullable()->after('cantidad_kg');
            $table->decimal('precio_kg', 12, 4)->nullable()->after('precio_tn');
            $table->decimal('flete_kg', 10, 4)->nullable()->after('precio_kg');
            $table->decimal('deducciones', 14, 2)->nullable()->after('flete_kg');
            $table->decimal('iva_deducciones', 14, 2)->nullable()->after('deducciones');
            $table->decimal('bonificacion', 14, 2)->nullable()->after('iva_deducciones');
            $table->decimal('ret_ganancias', 14, 2)->nullable()->after('bonificacion');
            $table->decimal('ret_iva', 14, 2)->nullable()->after('ret_ganancias');
            $table->decimal('iva_rg4310', 14, 2)->nullable()->after('ret_iva');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas_granos', function (Blueprint $table) {
            $table->dropColumn([
                'cantidad_kg', 'factor', 'precio_kg', 'flete_kg',
                'deducciones', 'iva_deducciones', 'bonificacion',
                'ret_ganancias', 'ret_iva', 'iva_rg4310',
            ]);
        });
    }
};
