<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('nombre', 150);
            $table->string('tipo', 20);
            $table->string('id_padre', 30)->nullable();
            $table->foreign('id_padre')->references('id')->on('ubicaciones')->restrictOnDelete();
            $table->timestamps();
        });
        foreach (['corrientes' => 'Corrientes', 'santa_fe' => 'Santa Fe', 'general' => 'General'] as $id => $nombre) {
            DB::table('ubicaciones')->insert(['id' => $id, 'nombre' => $nombre, 'tipo' => $id === 'general' ? 'otro' : 'provincia', 'created_at' => now(), 'updated_at' => now()]);
        }
        // Conservar cualquier clasificación histórica que no sea parte de las opciones base.
        foreach (['compras', 'proveedores', 'gastos_no_arca', 'establecimientos'] as $tabla) {
            foreach (DB::table($tabla)->whereNotNull('zona')->where('zona', '!=', '')->distinct()->pluck('zona') as $codigo) {
                if ($codigo === 'el_trebol') {
                    DB::table($tabla)->where('zona', $codigo)->update(['zona' => 'santa_fe']);

                    continue;
                }
                DB::table('ubicaciones')->insertOrIgnore(['id' => $codigo, 'nombre' => Str::headline($codigo), 'tipo' => 'zona', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->string('id_ubicacion', 30)->nullable()->unique();
            $table->foreign('id_ubicacion')->references('id')->on('ubicaciones')->restrictOnDelete();
        });
        foreach (DB::table('establecimientos')->get() as $campo) {
            $id = 'u_'.Str::lower(Str::random(24));
            DB::table('ubicaciones')->insert(['id' => $id, 'nombre' => $campo->nombre, 'tipo' => 'campo', 'id_padre' => $campo->zona ?: null, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('establecimientos')->where('id', $campo->id)->update(['id_ubicacion' => $id]);
            DB::table('compras')->where('id_establecimiento', $campo->id)->update(['zona' => $id]);
        }
    }

    public function down(): void
    {
        // Restituir una clasificación legible antes de eliminar el catálogo.
        $nodos = DB::table('ubicaciones')->get()->keyBy('id');
        foreach ($nodos as $nodo) {
            $raiz = $nodo;
            while ($raiz->id_padre && isset($nodos[$raiz->id_padre])) {
                $raiz = $nodos[$raiz->id_padre];
            }
            $zona = in_array($raiz->id, ['corrientes', 'santa_fe', 'general']) ? $raiz->id : 'general';
            foreach (['compras', 'proveedores', 'gastos_no_arca', 'establecimientos'] as $tabla) {
                DB::table($tabla)->where('zona', $nodo->id)->update(['zona' => $zona]);
            }
        }
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->dropForeign(['id_ubicacion']);
            $table->dropUnique(['id_ubicacion']);
            $table->dropColumn('id_ubicacion');
        });
        DB::table('ubicaciones')->update(['id_padre' => null]);
        Schema::dropIfExists('ubicaciones');
    }
};
