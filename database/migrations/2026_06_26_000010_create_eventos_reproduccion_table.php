<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_reproduccion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_establecimiento')->constrained('establecimientos')->cascadeOnDelete();
            $table->foreignUuid('id_animal')->nullable()
                ->constrained('animales')->nullOnDelete()
                ->comment('Hembra involucrada; null si no está individualmente registrada');

            $table->string('tipo_evento', 30)
                ->comment('servicio/inseminacion/diagnostico_prenez/parto/destete');
            $table->date('fecha');
            $table->string('resultado', 30)->nullable()
                ->comment('prenada/vacia/parto_simple/parto_gemelar/aborto');
            $table->string('toro_caravana', 30)->nullable()->comment('Identificación del toro si no está en el sistema');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->index('id_empresa');
            $table->index('id_establecimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_reproduccion');
    }
};
