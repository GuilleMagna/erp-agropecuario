<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corrales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_establecimiento')->nullable()->constrained('establecimientos')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->nullable();
            $table->unsignedInteger('capacidad_cabezas')->default(0);
            $table->decimal('superficie_m2', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['id_empresa', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrales');
    }
};
