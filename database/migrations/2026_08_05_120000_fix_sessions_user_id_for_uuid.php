<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La migración base de Laravel crea sessions.user_id como foreignId, es decir
 * unsignedBigInteger. Pero los usuarios de este sistema tienen UUID (ver
 * App\Traits\UsaUuid), así que con SESSION_DRIVER=database el driver de sesión
 * intenta escribir un UUID dentro de una columna numérica.
 *
 * Según el sql_mode del servidor eso termina en un error de escritura o en un
 * truncado silencioso a 0. En ninguno de los dos casos es correcto.
 *
 * En la base local la columna ya figura como varchar porque se la modificó a
 * mano en algún momento, pero no había ninguna migración que lo reflejara: una
 * instalación desde cero quedaba con el tipo equivocado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        // Los UUID no entran en un bigint: al revertir se pierden, así que se
        // vacía la columna antes de cambiar el tipo. Solo implica que las
        // sesiones vigentes dejan de tener usuario asociado.
        Schema::table('sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }
};
