<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Conciliación de VENTAS de SOCIEDAD, enero a junio de 2026, contra los Libros
 * IVA Ventas del contador. Las 6 ventas de granos del período son liquidaciones primarias.
 * Mismo criterio que se aplicó a ELVIO y WILMAR:
 *
 *  - se carga el número de liquidación y el débito fiscal, que no teníamos;
 *  - los importes ya coincidían con el libro, así que no se tocan.
 *
 * Las ventas de hacienda de SOCIEDAD (las que el libro informa como Liquida,
 * LIQ DE y Cta.de) no se tocan acá: el modelo de hacienda todavía no tiene
 * donde guardar el numero de comprobante ni el debito fiscal.
 *
 * Cada venta se identifica por fecha e importe, combinación única en el
 * período.
 */
return new class extends Migration
{
    private const CUIT_SOCIEDAD = '30-67486012-5';

    /** [fecha, importe_actual, numero_liquidacion, neto_libro, debito_fiscal] */
    private const LIQUIDACIONES = [
            ['2026-04-15', 14377116.60, '3301-30995813', 14377116.60, 1509597.24], // AFA/SOAGRO ok
            ['2026-04-20', 1983160.00, '3302-31036294', 1983160.00, 208231.80], // AFA/SOAGRO ok
            ['2026-05-27', 900998.00, '3302-31444654', 900998.00, 94604.79], // AFA/SOAGRO ok
            ['2026-05-29', 17782995.00, '3302-31490224', 17782995.00, 1867214.48], // AFA/SOAGRO ok
            ['2026-05-29', 9472050.00, '3302-31490231', 9472050.00, 994565.25], // AFA/SOAGRO ok
            ['2026-05-29', 14028120.00, '3302-31490233', 14028120.00, 1472952.60], // AFA/SOAGRO ok
    ];

    public function up(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_SOCIEDAD)->value('id');
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
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_SOCIEDAD)->value('id');
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
