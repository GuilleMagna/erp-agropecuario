<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reintegros_iva', function (Blueprint $table) {
            $table->uuid('id_empresa')->nullable()->after('id');

            $table->foreign('id_empresa')->references('id')->on('empresas')->nullOnDelete();

            $table->index('id_empresa');
        });
    }

    public function down(): void
    {
        Schema::table('reintegros_iva', function (Blueprint $table) {
            $table->dropForeign(['id_empresa']);
            $table->dropIndex(['id_empresa']);
            $table->dropColumn('id_empresa');
        });
    }
};
