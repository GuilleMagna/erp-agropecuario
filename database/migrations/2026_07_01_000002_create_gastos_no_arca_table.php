<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_no_arca', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa')->nullable()->constrained('empresas')->nullOnDelete();
            $table->string('nombre', 200);
            $table->string('categoria', 50)->default('otros');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('pagos_gasto_no_arca', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_gasto');
            $table->foreign('id_gasto')->references('id')->on('gastos_no_arca')->cascadeOnDelete();
            $table->date('mes')->comment('Primer día del mes: 2026-01-01');
            $table->decimal('importe', 14, 2)->default(0);
            $table->date('fecha_pago')->nullable();
            $table->string('notas', 300)->nullable();
            $table->timestamps();
            $table->unique(['id_gasto', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_gasto_no_arca');
        Schema::dropIfExists('gastos_no_arca');
    }
};
