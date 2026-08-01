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
        Schema::table('usuarios', function (Blueprint $table) {
            // Gobierna, por ahora, si recibe el informe por email de comprobantes
            // ARCA nuevos (ver SincronizarComprobantesArca). Genérico a propósito
            // para poder reusarlo con otras notificaciones futuras.
            $table->boolean('recibir_notificaciones')->default(true)->after('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('recibir_notificaciones');
        });
    }
};
