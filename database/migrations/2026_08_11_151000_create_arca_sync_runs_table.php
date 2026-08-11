<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arca_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_id', 100)->unique();
            $table->string('estado', 20)->default('running');
            $table->string('run_url')->nullable();
            $table->date('desde')->nullable();
            $table->date('hasta')->nullable();
            $table->unsignedInteger('empresas_total')->default(0);
            $table->unsignedInteger('empresas_procesadas')->default(0);
            $table->unsignedInteger('recibidos')->default(0);
            $table->unsignedInteger('importadas')->default(0);
            $table->unsignedInteger('duplicadas')->default(0);
            $table->unsignedInteger('errores')->default(0);
            $table->json('eventos')->nullable();
            $table->timestamp('iniciado_at')->nullable();
            $table->timestamp('finalizado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arca_sync_runs');
    }
};
