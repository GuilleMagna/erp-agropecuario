<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_campana')->constrained('campanas')->cascadeOnDelete();
            $table->foreignUuid('id_lote')->constrained('lotes')->cascadeOnDelete();
            $table->foreignUuid('id_siembra')->nullable()
                ->constrained('siembras')->nullOnDelete();

            $table->string('tipo_labor', 50);
            $table->date('fecha');
            $table->string('descripcion', 200)->nullable();
            $table->decimal('superficie_trabajada_ha', 10, 2)->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->index('id_empresa');
            $table->index('id_campana');
            $table->index('id_lote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labores');
    }
};
