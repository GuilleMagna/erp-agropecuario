<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orden en el que se muestran las empresas en los reportes.
     *
     * Hasta ahora se ordenaban por razón social, que daba ELVIO, SOCIEDAD,
     * WILMAR. El orden de trabajo es ELVIO, WILMAR, SOCIEDAD, así que pasa a
     * ser un dato de la empresa y no del alfabeto.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->unsignedSmallInteger('orden')->default(0)->after('razon_social');
        });

        foreach (['20-13543013-8' => 1, '20-17520408-4' => 2, '30-67486012-5' => 3] as $cuit => $orden) {
            DB::table('empresas')->where('cuit', $cuit)->update(['orden' => $orden]);
        }
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
};
