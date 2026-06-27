<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_empresa')->constrained('empresas')->cascadeOnDelete();

            $table->string('nombre', 100)->comment('Ej: "2024/25", "Campaña Fina 2025"');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->index('id_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanas');
    }
};
