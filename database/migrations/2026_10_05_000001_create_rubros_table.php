<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubros', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 60);
            $table->string('nombre', 100);
            $table->json('actividades');
            $table->string('actividad_default', 30);
            $table->timestamps();
            $table->unique(['id_empresa', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubros');
    }
};
