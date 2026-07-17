<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inmuebles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa')->nullable();
            $table->foreign('id_empresa')->references('id')->on('empresas')->nullOnDelete();

            $table->string('nombre', 150)->comment('ej. "Veneto V"');
            $table->string('localidad', 100)->nullable()->comment('ej. "Carlos Paz"');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmuebles');
    }
};
