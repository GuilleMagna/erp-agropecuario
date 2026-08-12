<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega "presentado" a los estados posibles de un reintegro.
     *
     * Se usa Schema::table()->change() en vez de un ALTER TABLE ... MODIFY
     * escrito a mano: MODIFY es sintaxis de MySQL y rompía la suite de tests,
     * que corre sobre SQLite en memoria.
     */
    public function up(): void
    {
        $this->cambiarEstados(['pendiente', 'presentado', 'acreditado', 'rechazado']);
    }

    public function down(): void
    {
        $this->cambiarEstados(['pendiente', 'acreditado', 'rechazado']);
    }

    private function cambiarEstados(array $estados): void
    {
        Schema::table('reintegros_iva', function (Blueprint $table) use ($estados) {
            $table->enum('estado', $estados)->default('pendiente')->change();
        });
    }
};
