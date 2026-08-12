<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conciliación de VENTAS de ELVIO, enero a junio de 2026, contra los Libros
 * IVA Ventas del contador. Las 43 operaciones del semestre son liquidaciones
 * primarias de granos.
 *
 * Tres cosas:
 *
 *  1. Se carga el número de liquidación y el débito fiscal en cada venta. No
 *     los teníamos: sin el número no había forma de cotejar contra el libro, y
 *     el reporte fiscal estimaba el IVA en vez de usar el declarado.
 *
 *  2. Se corrige el importe de 23 ventas. El libro declara siempre el bruto de
 *     la liquidación, pero el Excel del que se importaron traía a veces el
 *     líquido a cobrar, ya descontados flete y deducciones. Quedaban 8,5
 *     millones por debajo de lo presentado. Las deducciones y retenciones
 *     tienen su propio campo, así que no se pierde nada.
 *
 *  3. Falta una liquidación entera: 3302-31470754 de COOP, del 29/05.
 *
 * Cada venta se identifica por fecha e importe, que en el semestre es una
 * combinación única.
 */
return new class extends Migration
{
    private const CUIT_ELVIO = '20-13543013-8';

    /** [fecha, importe_actual, numero_liquidacion, neto_libro, debito_fiscal] */
    private const LIQUIDACIONES = [
            ['2026-01-16', 4675177.05, '3301-30383293', 4675177.05, 490893.59], // AGD ok
            ['2026-01-16', 5180959.48, '3301-30383302', 5180633.06, 543966.47], // AGD IMPORTE CORREGIDO
            ['2026-01-22', 9700000.00, '3302-30424953', 9700000.00, 1018500.00], // COOP ok
            ['2026-01-26', 1211124.72, '3301-30445368', 1211124.72, 127168.10], // SANCRISTOBAL ok
            ['2026-02-04', 2153118.64, '3301-30520256', 3131748.96, 328833.64], // AGD IMPORTE CORREGIDO
            ['2026-02-04', -28863.76, '3301-30520260', 1488022.70, 156242.38], // AGD IMPORTE CORREGIDO
            ['2026-03-11', 7509840.00, '6602-30736918', 7509840.00, 788533.20], // COOP ok
            ['2026-03-11', 12425199.00, '6602-30736943', 12425199.00, 1304645.90], // COOP ok
            ['2026-03-20', 8900712.00, '3301-30814937', 8900628.66, 934566.01], // SOAGRO IMPORTE CORREGIDO
            ['2026-03-23', 1182930.12, '3301-30823975', 1272977.68, 133662.66], // AGD IMPORTE CORREGIDO
            ['2026-03-26', 1901175.00, '3301-30843366', 2122677.58, 222881.15], // AGD IMPORTE CORREGIDO
            ['2026-04-06', 9225062.40, '3301-30910647', 10835324.23, 1137709.04], // AGD IMPORTE CORREGIDO
            ['2026-04-08', 7493962.38, '3301-30940117', 8812140.22, 925274.72], // AGD IMPORTE CORREGIDO
            ['2026-04-07', 11901633.75, '3302-30924718', 12352551.75, 1297017.93], // COOP IMPORTE CORREGIDO
            ['2026-04-13', 40684.14, '3301-30973263', 40815.39, 4285.62], // AGD IMPORTE CORREGIDO
            ['2026-04-13', 1803179.70, '3301-30973271', 1870582.05, 196411.12], // AGD IMPORTE CORREGIDO
            ['2026-04-16', 2398510.40, '3301-31012591', 2398510.40, 251843.59], // SANCRISTOBAL ok
            ['2026-04-14', 21500000.00, '3302-30986732', 21500000.00, 2257500.00], // COOP ok
            ['2026-04-20', 122264.34, '3301-31029622', 122644.72, 12877.70], // AGD IMPORTE CORREGIDO
            ['2026-04-21', 13760860.00, '3302-31041671', 13760860.00, 1444890.30], // COOP ok
            ['2026-04-21', 4958330.00, '3302-31041719', 4958330.00, 520624.65], // COOP ok
            ['2026-04-28', 19750906.40, '3301-31102434', 19750777.73, 2073831.66], // AGD IMPORTE CORREGIDO
            ['2026-04-27', 17160000.00, '3302-31089105', 17160000.00, 1801800.00], // COOP ok
            ['2026-04-30', 1450290.00, '3302-31129442', 1450290.00, 152280.45], // COOP ok
            ['2026-04-30', 12275700.00, '3302-31129655', 12275700.00, 1288948.50], // COOP ok
            ['2026-04-30', 7288860.00, '3302-31129730', 7288860.00, 765330.30], // COOP ok
            ['2026-04-30', 735150.00, '3302-31129792', 735150.00, 77190.75], // COOP ok
            ['2026-04-30', 6286738.64, '3302-31130143', 6524841.04, 685108.31], // COOP IMPORTE CORREGIDO
            ['2026-05-07', 12813570.00, '3301-31201800', 12813574.52, 1345425.32], // SOAGRO IMPORTE CORREGIDO
            ['2026-05-11', 910462.24, '3301-31230151', 917595.69, 96347.55], // AGD IMPORTE CORREGIDO
            ['2026-05-27', 9685829.40, '3301-31441437', 9685829.40, 1017012.09], // SANCRISTOBAL ok
            ['2026-05-28', 51606000.00, '3301-31450742', 53423832.00, 5609502.36], // AGD IMPORTE CORREGIDO
            ['2026-05-29', 1160802.50, '3302-31470631', 1160802.50, 121884.26], // COOP ok
            ['2026-06-05', 9167532.00, '3302-31561925', 9213700.00, 967438.50], // COOP IMPORTE CORREGIDO
            ['2026-06-10', 9052600.00, '3301-31608319', 9080246.00, 953425.83], // AGD IMPORTE CORREGIDO
            ['2026-06-10', 7580496.00, '3302-31608084', 7580496.00, 795952.08], // COOP ok
            ['2026-06-10', 20093144.62, '3302-31608111', 20193893.15, 2120358.78], // COOP IMPORTE CORREGIDO
            ['2026-06-10', 27137574.00, '3302-31608132', 27137574.00, 2849445.27], // COOP ok
            ['2026-06-10', 13618600.00, '3302-31608161', 13618600.00, 1429953.00], // COOP ok
            ['2026-06-18', 4299327.72, '3301-31680770', 4312409.77, 452803.03], // AGD IMPORTE CORREGIDO
            ['2026-06-18', 1458299.55, '3301-31680779', 1509668.45, 158515.19], // AGD IMPORTE CORREGIDO
            ['2026-06-18', 3301390.10, '3301-31680783', 3310944.44, 347649.17], // AGD IMPORTE CORREGIDO
    ];

    public function up(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        if (! $idEmpresa) {
            return;
        }

        foreach (self::LIQUIDACIONES as [$fecha, $importeActual, $numero, $neto, $debito]) {
            // Se busca por el importe que tiene hoy; si la migración ya corrió,
            // el registro figura con el neto del libro y se lo encuentra igual.
            $id = DB::table('ventas_granos')
                ->where('id_empresa', $idEmpresa)
                ->whereDate('fecha', $fecha)
                ->where(function ($q) use ($importeActual, $neto) {
                    $q->whereBetween('importe_total', [$importeActual - 0.01, $importeActual + 0.01])
                        ->orWhereBetween('importe_total', [$neto - 0.01, $neto + 0.01]);
                })
                ->value('id');

            if (! $id) {
                continue;
            }

            DB::table('ventas_granos')->where('id', $id)->update([
                'numero_comprobante' => $numero,
                'importe_total' => $neto,
                'debito_fiscal' => $debito,
                'updated_at' => now(),
            ]);
        }

        // Liquidación que no estaba cargada.
        $existe = DB::table('ventas_granos')
            ->where('id_empresa', $idEmpresa)
            ->where('numero_comprobante', '3302-31470754')
            ->exists();

        if (! $existe) {
            DB::table('ventas_granos')->insert([
                'id' => (string) Str::uuid(),
                'id_empresa' => $idEmpresa,
                'comprador' => 'COOP ET',
                'cereal' => 'soja',
                'tipo_venta' => 'disponible',
                'numero_comprobante' => '3302-31470754',
                'fecha' => '2026-05-29',
                'cantidad_tn' => 0,
                'cantidad_kg' => 0,
                'factor' => 100,
                'precio_tn' => 0,
                'precio_kg' => 0,
                'flete_kg' => 0,
                'deducciones' => 0,
                'iva_deducciones' => 0,
                'bonificacion' => 0,
                'ret_ganancias' => 0,
                'ret_iva' => 0,
                'iva_rg4310' => 0,
                'debito_fiscal' => 460440.75,
                'moneda' => 'ARS',
                'importe_total' => 4385150.00,
                'estado' => 'cobrada',
                'observaciones' => 'Alta manual: figuraba en el Libro IVA Ventas de mayo 2026 y no estaba en el ERP. '
                    .'Faltan los kilos y el precio, que el libro no informa.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        if (! $idEmpresa) {
            return;
        }

        foreach (self::LIQUIDACIONES as [$fecha, $importeActual, $numero, $neto, $debito]) {
            DB::table('ventas_granos')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->update([
                    'numero_comprobante' => null,
                    'importe_total' => $importeActual,
                    'debito_fiscal' => 0,
                    'updated_at' => now(),
                ]);
        }

        DB::table('ventas_granos')
            ->where('id_empresa', $idEmpresa)
            ->where('numero_comprobante', '3302-31470754')
            ->delete();
    }
};
