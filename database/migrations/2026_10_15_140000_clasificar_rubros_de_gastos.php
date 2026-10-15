<?php

use App\Models\GastoNoArca;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_no_arca', fn (Blueprint $table) => $table->string('rubro', 60)->nullable()->index());
        DB::table('gastos_no_arca')->orderBy('id')->chunkById(200, function ($gastos) {
            foreach ($gastos as $g) {
                DB::table('gastos_no_arca')->where('id', $g->id)->update(['rubro' => GastoNoArca::rubroSugerido($g->nombre, $g->categoria)]);
            }
        });
        DB::table('proveedores')->orderBy('id')->chunkById(200, function ($proveedores) {
            foreach ($proveedores as $p) {
                if (preg_match('/\bepe\b|empresa provincial (?:de (?:la )?)?energia/', Str::lower(Str::ascii($p->nombre)))) {
                    DB::table('proveedores')->where('id', $p->id)->update(['rubro' => 'energia']);
                    DB::table('compras')->where('id_empresa', $p->id_empresa)->where('id_proveedor', $p->id)->update(['rubro' => 'energia']);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('gastos_no_arca', fn (Blueprint $table) => $table->dropColumn('rubro'));
    }
};
