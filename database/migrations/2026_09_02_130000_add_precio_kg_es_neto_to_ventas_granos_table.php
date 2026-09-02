<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas_granos', function (Blueprint $table) {
            $table->boolean('precio_kg_es_neto')->default(false)->after('precio_kg');
        });
    }

    public function down(): void
    {
        Schema::table('ventas_granos', function (Blueprint $table) {
            $table->dropColumn('precio_kg_es_neto');
        });
    }
};
