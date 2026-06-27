<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_establecimiento')->constrained('establecimientos')->cascadeOnDelete();

            $table->string('nombre', 100);
            $table->string('codigo', 30)->nullable()->comment('Código corto de identificación');
            $table->string('tipo', 20)->default('agricola')
                ->comment('agricola / ganadero / mixto / forestal / sin_uso');
            $table->decimal('superficie_ha', 10, 2)->nullable();
            $table->decimal('latitud', 10, 6)->nullable();
            $table->decimal('longitud', 10, 6)->nullable();
            $table->text('descripcion')->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('id_empresa');
            $table->index('id_establecimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
