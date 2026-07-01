<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('arca_cuit_login', 13)->nullable()->after('activa')
                ->comment('CUIT con el que se loguea en AFIP (puede ser el de un representante)');
            $table->text('arca_clave_fiscal')->nullable()->after('arca_cuit_login')
                ->comment('Clave fiscal encriptada');
            $table->string('arca_cuit_representado', 13)->nullable()->after('arca_clave_fiscal')
                ->comment('CUIT de esta empresa en ARCA (a seleccionar en pantalla de personas)');
            $table->string('arca_nombre_representado', 200)->nullable()->after('arca_cuit_representado');
            $table->boolean('arca_activo')->default(false)->after('arca_nombre_representado');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'arca_cuit_login',
                'arca_clave_fiscal',
                'arca_cuit_representado',
                'arca_nombre_representado',
                'arca_activo',
            ]);
        });
    }
};
