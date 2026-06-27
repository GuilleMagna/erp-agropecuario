<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesajes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('id_establecimiento')->constrained('establecimientos')->cascadeOnDelete();
            $table->foreignUuid('id_animal')->nullable()
                ->constrained('animales')->nullOnDelete()
                ->comment('Null = pesaje grupal por categoría');

            $table->date('fecha');
            $table->string('categoria', 20)->nullable()
                ->comment('Para pesajes grupales o cuando el animal no está en el sistema');
            $table->integer('cantidad')->default(1)
                ->comment('1 para individual, N para grupal');
            $table->decimal('peso_kg', 8, 2)
                ->comment('Peso individual o promedio del grupo');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->index('id_empresa');
            $table->index('id_establecimiento');
            $table->index('id_animal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesajes');
    }
};
