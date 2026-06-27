<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->enum('actividad', ['agricultura', 'ganaderia', 'feedlot', 'general'])
                  ->nullable()
                  ->default('general')
                  ->after('observaciones');

            $table->uuid('id_lote')->nullable()->after('actividad');
            $table->uuid('id_campana')->nullable()->after('id_lote');

            $table->foreign('id_lote')
                  ->references('id')
                  ->on('lotes')
                  ->nullOnDelete();

            $table->foreign('id_campana')
                  ->references('id')
                  ->on('campanas')
                  ->nullOnDelete();

            $table->index('actividad');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['id_lote']);
            $table->dropForeign(['id_campana']);
            $table->dropIndex(['actividad']);
            $table->dropColumn(['actividad', 'id_lote', 'id_campana']);
        });
    }
};
