<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reintegros_iva', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('periodo', 7)->comment('Formato YYYY-MM');
            $table->uuid('id_periodo_fiscal')->nullable();
            $table->decimal('importe', 15, 2);
            $table->date('fecha_presentacion')->nullable();
            $table->date('fecha_acreditacion')->nullable();
            $table->enum('estado', ['pendiente', 'acreditado', 'rechazado'])->default('pendiente');
            $table->string('numero_expediente', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_periodo_fiscal')
                  ->references('id')
                  ->on('periodos_fiscales')
                  ->nullOnDelete();

            $table->index('periodo');
            $table->index('estado');
            $table->index('id_periodo_fiscal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reintegros_iva');
    }
};
