<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Conciliación de VENTAS de WILMAR, enero a junio de 2026, contra los Libros
 * IVA Ventas del contador. Las 37 operaciones del período son liquidaciones
 * primarias de granos. Mismo criterio que se aplicó a ELVIO:
 *
 *  - se carga el número de liquidación y el débito fiscal, que no teníamos;
 *  - el importe pasa a ser el bruto de la liquidación, que es lo que declara
 *    el libro. El Excel del que se importaron traía en 21 de las 37 el líquido
 *    a cobrar, ya descontados flete y deducciones, que tienen su propio campo.
 *
 * Febrero no tiene libro porque no hubo operaciones ese mes.
 *
 * Cada venta se identifica por fecha e importe, combinación única en el
 * período.
 */
return new class extends Migration
{
    private const CUIT_WILMAR = '20-17520408-4';

    /** [fecha, importe_actual, numero_liquidacion, neto_libro, debito_fiscal] */
    private const LIQUIDACIONES = [
            ['2026-01-16', 6831012.12, '03301-30383311', 6831012.12, 717256.27], // AGD ok
            ['2026-01-16', 4481335.82, '03301-30383321', 4481335.82, 470540.26], // AGD ok
            ['2026-01-26', 2997435.05, '3301-30445426', 2997435.05, 314730.68], // SANCRISTOBAL ok
            ['2026-03-11', 7411757.20, '03302-30736774', 7411908.30, 778250.37], // COOP IMPORTE CORREGIDO
            ['2026-03-11', 7307204.00, '03302-30736813', 7307204.00, 767256.42], // COOP ok
            ['2026-03-20', 6848550.00, '03301-30814888', 6848485.88, 719091.02], // SOAGRO IMPORTE CORREGIDO
            ['2026-03-23', 524178.61, '03301-30824094', 548170.86, 57557.94], // AGD IMPORTE CORREGIDO
            ['2026-03-26', 409516.95, '3301-30843388', 487076.67, 51143.05], // AGD IMPORTE CORREGIDO
            ['2026-03-26', 1836742.97, '3301-30843404', 1848103.73, 194050.89], // AGD IMPORTE CORREGIDO
            ['2026-04-06', 11716342.50, '3301-30910665', 12208691.01, 1281912.56], // AGD IMPORTE CORREGIDO
            ['2026-04-08', 5332718.37, '3301-30941097', 6542039.93, 686914.19], // AGD IMPORTE CORREGIDO
            ['2026-04-08', 282149.80, '3301-30941965', 294013.57, 30871.42], // AGD IMPORTE CORREGIDO
            ['2026-04-08', 6653071.33, '3301-30941988', 6869666.55, 721314.99], // AGD IMPORTE CORREGIDO
            ['2026-04-07', 7906273.44, '3302-30924665', 8244195.09, 865640.48], // COOP IMPORTE CORREGIDO
            ['2026-04-13', 1901491.20, '3301-30973343', 1977870.59, 207676.41], // AGD IMPORTE CORREGIDO
            ['2026-04-16', 2306143.84, '3301-31013963', 2306143.84, 242145.10], // SANCRISTOBAL ok
            ['2026-04-14', 21500000.00, '3302-30986779', 21500000.00, 2257500.00], // COOP ok
            ['2026-04-20', 81214.08, '3301-31029668', 81473.45, 8554.71], // AGD IMPORTE CORREGIDO
            ['2026-04-28', 13704943.77, '3301-31102447', 13704954.67, 1439020.24], // AGD IMPORTE CORREGIDO
            ['2026-04-27', 17160000.00, '3302-31089015', 17160000.00, 1801800.00], // COOP ok
            ['2026-04-30', 6807330.00, '3302-31128607', 6807330.00, 714769.65], // COOP ok
            ['2026-04-30', 1378150.00, '3302-31128704', 1378150.00, 144705.75], // COOP ok
            ['2026-04-30', 7024815.00, '3302-31128860', 7024815.00, 737605.58], // COOP ok
            ['2026-04-30', 1585452.33, '3302-31128956', 1617808.50, 169869.89], // COOP IMPORTE CORREGIDO
            ['2026-04-30', 7307285.03, '3302-31129032', 7456413.30, 782923.40], // COOP IMPORTE CORREGIDO
            ['2026-04-30', 210975.00, '3302-31129104', 210975.00, 22152.38], // COOP ok
            ['2026-04-30', 327117.04, '3302-31129185', 333792.90, 35048.25], // COOP IMPORTE CORREGIDO
            ['2026-05-07', 12421926.00, '3301-31201658', 12421926.00, 1304302.23], // SOAGRO ok
            ['2026-05-11', 770193.45, '3301-31230394', 777397.06, 81626.69], // AGD IMPORTE CORREGIDO
            ['2026-05-11', 20407500.00, '3301-31230526', 21217305.00, 2227817.03], // AGD IMPORTE CORREGIDO
            ['2026-05-27', 9863926.80, '3301-31441673', 9863926.80, 1035712.31], // SANCRISTOBAL ok
            ['2026-05-28', 30809832.30, '3301-31450762', 31975854.32, 3357464.70], // AGD IMPORTE CORREGIDO
            ['2026-05-28', 3663785.78, '3301-31450776', 3674705.16, 385844.04], // AGD IMPORTE CORREGIDO
            ['2026-06-10', 3396624.00, '3302-31607920', 3396624.00, 356645.52], // COOP ok
            ['2026-06-10', 26734500.00, '3302-31607959', 26734500.00, 2807122.50], // COOP ok
            ['2026-06-10', 25638122.27, '3302-31607989', 25897093.20, 2719194.79], // COOP IMPORTE CORREGIDO
            ['2026-06-10', 12296956.00, '3302-31608017', 12296956.00, 1291180.38], // COOP ok
    ];

    public function up(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_WILMAR)->value('id');
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
    }

    public function down(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_WILMAR)->value('id');
        if (! $idEmpresa) {
            return;
        }

        foreach (self::LIQUIDACIONES as [$fecha, $importeActual, $numero]) {
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
    }
};
