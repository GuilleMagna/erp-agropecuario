<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas_granos', function (Blueprint $table) {
            $table->string('unidad_cantidad', 12)->default('kg')->after('cantidad_kg');
        });
    }

    public function down(): void
    {
        Schema::table('ventas_granos', function (Blueprint $table) {
            $table->dropColumn('unidad_cantidad');
        });
    }
};
