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
        Schema::table('compras', function (Blueprint $table) {
            // "inversiones" faltaba en la clasificación de actividad (Excel de
            // origen "Facturas AÑO 2024.xlsx", hoja RUBROS).
            $table->enum('actividad', ['agricultura', 'ganaderia', 'feedlot', 'general', 'inversiones'])
                ->nullable()
                ->default('general')
                ->change();

            $table->string('zona', 30)->nullable()->after('actividad');
            $table->string('rubro', 60)->nullable()->after('zona');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn(['zona', 'rubro']);

            $table->enum('actividad', ['agricultura', 'ganaderia', 'feedlot', 'general'])
                ->nullable()
                ->default('general')
                ->change();
        });
    }
};
