<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conciliación de las VENTAS DE HACIENDA de SOCIEDAD, enero a junio de 2026,
 * contra los Libros IVA Ventas. El libro las informa como Liquida, LIQ DE y
 * Cta.de; las LIQ.111 son granos y se concilian aparte.
 *
 * Se carga el número de liquidación y el débito fiscal, y el importe pasa a
 * ser el neto que declara el libro. En las ventas por consignación (Colombo y
 * Magliano) el ERP tenía el bruto y el libro declara el neto de comisión.
 *
 * Tres casos que no salían de un cruce directo:
 *
 *  - Las liquidaciones del 06/04 son las dos por 13.000.000 el mismo día, así
 *    que se distinguen por comprador: la 0100-00000016 es la venta a WILMAR y
 *    la 0100-00000017 la venta a ELVIO, según el libro de ventas y confirmado
 *    contra los libros de compras de cada una.
 *  - La liquidación 3004-00027096 del 28/04 está partida en dos ventas en el
 *    ERP. Se le asigna el número a las dos y el neto y el débito se reparten
 *    en proporción al importe, de modo que la suma dé exactamente el libro.
 *  - Las dos ventas del 29/05 no figuran en el libro de ventas de mayo, pero
 *    sí en los libros de compras de ELVIO y WILMAR, que las tienen como
 *    0100-00000018 y 0100-00000019. De ahí salen el número y el débito.
 */
return new class extends Migration
{
    private const CUIT_SOCIEDAD = '30-67486012-5';

    /** [fecha, comprador, importe_actual, numero, neto_libro, debito_fiscal] */
    private const LIQUIDACIONES = [
        ['2026-01-23', 'QUITFOOD', 67325890.74, '3004-00025952', 67325890.83, 7069218.54],
        ['2026-02-11', 'RIOPLATENSE', 67408804.80, '0018-00023516', 67408800.00, 7077924.00],
        ['2026-04-06', 'WILMAR MAGNANO', 13000000.00, '0100-00000016', 13000000.00, 1365000.00],
        ['2026-04-06', 'ELVIO MAGNANO', 13000000.00, '0100-00000017', 13000000.00, 1365000.00],
        ['2026-04-28', 'QUITFOOD', 64512066.60, '3004-00027096', 64512013.51, 6773761.41],
        ['2026-04-28', 'QUITFOOD', 18768011.60, '3004-00027096', 18767996.16, 1970639.60],
        ['2026-05-11', 'COLOMBO Y MAGLIANO', 34522800.00, '1041-00001749', 33487116.00, 3516147.18],
        ['2026-05-12', 'COLOMBO Y MAGLIANO', 96179200.00, '1038-00005676', 92297088.24, 9691194.27],
        ['2026-05-29', 'ELVIO MAGNANO', 44800000.00, '0100-00000018', 44800000.00, 4704000.00],
        ['2026-05-29', 'WILMAR MAGNANO', 44800000.00, '0100-00000019', 44800000.00, 4704000.00],
        ['2026-05-31', 'COLOMBO Y MAGLIANO', 125781900.00, '1040-00004551', 122008442.70, 12810886.48],
        ['2026-06-18', 'QUITFOOD', 85560006.63, '3004-00027778', 85560006.63, 8983800.70],
    ];

    /** Las del 28/06 no estaban cargadas. [fecha, comprador, numero, neto, debito] */
    private const FALTANTES = [
        ['2026-06-28', 'ELVIO MAGNANO', '0100-00000020', 60000000.00, 6300000.00],
        ['2026-06-28', 'WILMAR MAGNANO', '0100-00000021', 61200000.00, 6426000.00],
    ];

    public function up(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_SOCIEDAD)->value('id');
        if (! $idEmpresa) {
            return;
        }

        foreach (self::LIQUIDACIONES as [$fecha, $comprador, $importeActual, $numero, $neto, $debito]) {
            // Se busca por el importe que tiene hoy; si la migración ya corrió,
            // el registro figura con el neto del libro y se lo encuentra igual.
            $id = DB::table('ventas_hacienda')
                ->where('id_empresa', $idEmpresa)
                ->whereDate('fecha', $fecha)
                ->where('comprador', $comprador)
                ->where(function ($q) use ($importeActual, $neto) {
                    $q->whereBetween('importe_total', [$importeActual - 0.01, $importeActual + 0.01])
                        ->orWhereBetween('importe_total', [$neto - 0.01, $neto + 0.01]);
                })
                ->value('id');

            if (! $id) {
                continue;
            }

            DB::table('ventas_hacienda')->where('id', $id)->update([
                'numero_comprobante' => $numero,
                'importe_total' => $neto,
                'debito_fiscal' => $debito,
                'updated_at' => now(),
            ]);
        }

        foreach (self::FALTANTES as [$fecha, $comprador, $numero, $neto, $debito]) {
            $existe = DB::table('ventas_hacienda')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->exists();

            if ($existe) {
                continue;
            }

            DB::table('ventas_hacienda')->insert([
                'id' => (string) Str::uuid(),
                'id_empresa' => $idEmpresa,
                'comprador' => $comprador,
                'numero_comprobante' => $numero,
                'fecha' => $fecha,
                'tipo_operacion' => 'terminado',
                'categoria' => 'novillo',
                'importe_total' => $neto,
                'debito_fiscal' => $debito,
                'moneda' => 'ARS',
                'estado' => 'cobrada',
                'observaciones' => 'Alta manual: figuraba en el Libro IVA Ventas de junio 2026 y no estaba en el ERP. '
                    .'Faltan las cabezas y el peso, que el libro no informa.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_SOCIEDAD)->value('id');
        if (! $idEmpresa) {
            return;
        }

        foreach (self::LIQUIDACIONES as [$fecha, $comprador, $importeActual, $numero, $neto]) {
            DB::table('ventas_hacienda')
                ->where('id_empresa', $idEmpresa)
                ->whereDate('fecha', $fecha)
                ->where('comprador', $comprador)
                ->whereBetween('importe_total', [$neto - 0.01, $neto + 0.01])
                ->update([
                    'numero_comprobante' => null,
                    'importe_total' => $importeActual,
                    'debito_fiscal' => 0,
                    'updated_at' => now(),
                ]);
        }

        foreach (self::FALTANTES as [, , $numero]) {
            DB::table('ventas_hacienda')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->delete();
        }
    }
};
