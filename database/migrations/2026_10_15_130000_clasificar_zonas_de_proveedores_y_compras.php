<?php

use App\Models\Proveedor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('proveedores', 'zona')) {
            Schema::table('proveedores', fn (Blueprint $table) => $table->string('zona', 30)->nullable());
        }
        DB::table('proveedores')->orderBy('id')->chunkById(200, function ($proveedores) {
            foreach ($proveedores as $p) {
                $zona = in_array($p->zona, ['santa_fe', 'corrientes'], true) ? $p->zona
                    : Proveedor::inferirZona($p->provincia, $p->ciudad, $p->direccion);
                DB::table('proveedores')->where('id', $p->id)->update(['zona' => $zona]);
                DB::table('compras')->where('id_empresa', $p->id_empresa)->where('id_proveedor', $p->id)
                    ->where(fn ($q) => $q->whereNull('zona')->orWhereIn('zona', ['', 'general', 'el_trebol']))
                    ->update(['zona' => $zona]);
            }
        });
    }

    public function down(): void
    {
        // Conservar zonas ya revisadas en proveedores y comprobantes.
    }
};
