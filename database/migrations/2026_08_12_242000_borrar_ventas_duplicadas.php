<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Borra dos ventas de granos que quedaron cargadas dos veces al importar el
 * Excel de control mensual. En los dos casos el duplicado no figura en ningún
 * Libro IVA Ventas y su gemelo sí.
 *
 *  - SOCIEDAD, 15/04, SOLUCIONES AGROPECUARIAS, 29,820 tn por 14.377.116,60:
 *    dos registros idénticos creados en la misma importación. El libro declara
 *    una sola liquidación, la 3301-30995813.
 *  - WILMAR, 30/04, COOP ET, 28,220 tn por 12.275.700: la misma operación está
 *    en ELVIO con la liquidación 3302-31129655, que figura en el libro de
 *    ELVIO. En el de WILMAR no aparece.
 *
 * Se borra el registro que quedó sin número de liquidación, para no tocar el
 * que ya está conciliado.
 */
return new class extends Migration
{
    public function up(): void
    {
        // En SOCIEDAD la operación existe: queda una sola de las dos.
        $this->duplicado('30-67486012-5', '2026-04-15', 14377116.60, 1);
        // En WILMAR la operación no es suya: se va la única que hay.
        $this->duplicado('20-17520408-4', '2026-04-30', 12275700.00, 0);
    }

    /**
     * Borra los sobrantes sin número de liquidación, dejando $aConservar.
     * No borra nada si no encuentra exactamente lo que espera.
     */
    private function duplicado(string $cuit, string $fecha, float $importe, int $aConservar): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', $cuit)->value('id');
        if (! $idEmpresa) {
            return;
        }

        $filas = DB::table('ventas_granos')
            ->where('id_empresa', $idEmpresa)
            ->whereDate('fecha', $fecha)
            ->whereBetween('importe_total', [$importe - 0.01, $importe + 0.01])
            ->orderByRaw('numero_comprobante IS NULL')   // primero los que ya tienen número
            ->get(['id', 'numero_comprobante']);

        if ($filas->count() <= $aConservar) {
            return;   // ya se corrigió, o los datos no son los esperados
        }

        $sobran = $filas->slice($aConservar)->filter(fn ($f) => $f->numero_comprobante === null);

        if ($sobran->isNotEmpty()) {
            DB::table('ventas_granos')->whereIn('id', $sobran->pluck('id'))->delete();
        }
    }

    public function down(): void
    {
        // No se restauran: eran duplicados de registros que siguen existiendo.
    }
};
