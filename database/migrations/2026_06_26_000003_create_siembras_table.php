<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siembras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_campana')->constrained('campanas')->cascadeOnDelete();
            $table->foreignUuid('id_lote')->constrained('lotes')->cascadeOnDelete();

            $table->string('cultivo', 60);
            $table->string('variedad', 100)->nullable();
            $table->date('fecha_siembra');
            $table->decimal('superficie_sembrada_ha', 10, 2);
            $table->decimal('densidad_siembra', 10, 2)->nullable()->comment('kg/ha o semillas/ha');
            $table->string('estado', 20)->default('sembrada')
                ->comment('planificada / sembrada / en_cultivo / cosechada / perdida');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->index('id_empresa');
            $table->index('id_campana');
            $table->index('id_lote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siembras');
    }
};
