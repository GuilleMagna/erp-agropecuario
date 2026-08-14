<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * La liquidación 0005-00026563 del 15/05/2026, que ELVIO tiene como compra por
 * 62.001.180,79, figura en el Libro IVA Compras con el CUIT de la sociedad
 * (30-67486012-5). No corresponde: SOCIEDAD no tiene esa venta, y las otras
 * dos liquidaciones de la misma serie y del mismo día son de Haciendas Villa
 * María.
 *
 *   0005-00026563  ELVIO      62.001.180,79   figuraba como MAGNANO H, E Y W
 *   0005-00026564  WILMAR     55.580.427,22   Haciendas Villa María
 *   0005-00026571  SOCIEDAD  144.990.869,85   Haciendas Villa María
 *
 * Es el mismo emisor vendiéndole a las tres empresas el mismo día, no una
 * operación entre ellas. Con el emisor corregido, las 13 operaciones del grupo
 * quedan emparejadas de los dos lados.
 */
return new class extends Migration
{
    private const CUIT_ELVIO = '20-13543013-8';

    private const CUIT_VILLA_MARIA = '30-58511655-2';

    private const NUMERO = '0005-00026563';

    private const FECHA = '2026-05-15';

    public function up(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        if (! $idEmpresa) {
            return;
        }

        $compra = DB::table('compras')
            ->where('id_empresa', $idEmpresa)
            ->where('numero_comprobante', self::NUMERO)
            ->whereDate('fecha', self::FECHA)
            ->first(['id', 'id_proveedor', 'observaciones']);

        if (! $compra) {
            return;
        }

        $idProveedor = DB::table('proveedores')
            ->where('id_empresa', $idEmpresa)
            ->where('cuit', self::CUIT_VILLA_MARIA)
            ->value('id');

        if (! $idProveedor) {
            $idProveedor = (string) Str::uuid();
            DB::table('proveedores')->insert([
                'id' => $idProveedor,
                'id_empresa' => $idEmpresa,
                'nombre' => 'HACIENDAS VILLA MARIA',
                'razon_social' => 'HACIENDAS VILLA MARIA',
                'cuit' => self::CUIT_VILLA_MARIA,
                'rubro' => 'otro',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($compra->id_proveedor === $idProveedor) {
            return;   // ya estaba corregida
        }

        DB::table('compras')->where('id', $compra->id)->update([
            'id_proveedor' => $idProveedor,
            'observaciones' => trim(($compra->observaciones ? $compra->observaciones.' | ' : '')
                .'Emisor corregido a Haciendas Villa María: el libro traía el CUIT de la sociedad, '
                .'que no tiene esta venta.'),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        $idSociedad = DB::table('proveedores')->where('id_empresa', $idEmpresa)
            ->where('cuit', '30-67486012-5')->value('id');

        if (! $idEmpresa || ! $idSociedad) {
            return;
        }

        DB::table('compras')
            ->where('id_empresa', $idEmpresa)
            ->where('numero_comprobante', self::NUMERO)
            ->whereDate('fecha', self::FECHA)
            ->update(['id_proveedor' => $idSociedad, 'updated_at' => now()]);
    }
};
