<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conciliación de COMPRAS de ELVIO, enero 2026, contra el Libro IVA Compras
 * que presentó el contador ante ARCA.
 *
 * Tres correcciones, todas idempotentes (se pueden correr más de una vez):
 *
 *  1. Faltaban 5 comprobantes de MERLO NATALIA (tiques-factura A y sus notas
 *     de crédito). No estaban en el sistema en ninguna empresa ni mes.
 *  2. La N/C de SOLUCIONES AGROPECUARIAS 0006-00001050 estaba cargada como
 *     tipo "otro" y en positivo. El importador de ARCA mapeaba todas las notas
 *     de crédito a "otro" sin invertir el signo, así que sumaba en lugar de
 *     restar (ver MrbotService::MAPA_TIPOS).
 *  3. 13 comprobantes que están en el ERP quedaron fuera de la presentación.
 *     Se marcan con presentado_arca = false para que el reporte fiscal pueda
 *     cuadrar contra el libro sin sacarlos del sistema.
 */
return new class extends Migration
{
    private const CUIT_ELVIO = '20-13543013-8';

    private const CUIT_MERLO = '30-71684948-8';

    /** [numero, fecha, tipo, subtotal, iva, total] tal como figuran en el libro. */
    private const COMPROBANTES_MERLO = [
        ['0004-00009283', '2026-01-02', 'ticket',        32384.72,  6800.79,  39185.51],
        ['0004-00009696', '2026-01-02', 'ticket',        14615.81,  3069.32,  17685.13],
        ['0004-00003445', '2026-01-02', 'nota_credito',  -6476.94, -1360.16,  -7837.10],
        ['0004-00003627', '2026-01-02', 'nota_credito',  -2923.16,  -613.86,  -3537.02],
        ['0004-00009765', '2026-01-05', 'ticket',        24313.30,  5105.79,  29419.09],
    ];

    /** Comprobantes del ERP que no figuran en el libro IVA presentado. */
    private const NO_PRESENTADOS = [
        '0031-05775879', '0018-00005225', '0017-00001006', '0018-00005590',
        '0041-01306206', '0007-00079068', '9095-00164970', '0005-00009167',
        '0002-00040446', '1996-00014224', '0002-00275862', '0002-00225973',
        '0004-08016999',
    ];

    public function up(): void
    {
        $empresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->first();
        if (! $empresa) {
            return; // Base sin la empresa: nada que conciliar.
        }

        // ── 1. Alta de los 5 comprobantes de MERLO NATALIA ───────────────
        $proveedor = DB::table('proveedores')
            ->where('cuit', self::CUIT_MERLO)
            ->where('id_empresa', $empresa->id)
            ->first();

        if (! $proveedor) {
            $idProveedor = (string) Str::uuid();
            DB::table('proveedores')->insert([
                'id' => $idProveedor,
                'id_empresa' => $empresa->id,
                'nombre' => 'MERLO NATALIA',
                'razon_social' => 'MERLO NATALIA',
                'cuit' => self::CUIT_MERLO,
                'rubro' => 'otro',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $idProveedor = $proveedor->id;
        }

        foreach (self::COMPROBANTES_MERLO as [$numero, $fecha, $tipo, $subtotal, $iva, $total]) {
            $yaEsta = DB::table('compras')
                ->where('id_empresa', $empresa->id)
                ->where('numero_comprobante', $numero)
                ->where('id_proveedor', $idProveedor)
                ->exists();

            if ($yaEsta) {
                continue;
            }

            DB::table('compras')->insert([
                'id' => (string) Str::uuid(),
                'id_empresa' => $empresa->id,
                'id_proveedor' => $idProveedor,
                'id_establecimiento' => null,
                'tipo_comprobante' => $tipo,
                'numero_comprobante' => $numero,
                'fecha' => $fecha,
                'estado' => 'pagada',
                'subtotal' => $subtotal,
                'iva_porc' => 21,
                'iva_importe' => $iva,
                'total' => $total,
                'stock_registrado' => false,
                'presentado_arca' => true,
                'actividad' => 'general',
                'observaciones' => 'Alta manual: figuraba en el Libro IVA Compras de enero 2026 y no estaba en el ERP.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── 2. N/C de SOLUCIONES AGROPECUARIAS en positivo ───────────────
        // Sólo se toca si sigue con el signo invertido, para no dar vuelta un
        // registro ya corregido a mano.
        DB::table('compras')
            ->where('id_empresa', $empresa->id)
            ->where('numero_comprobante', '0006-00001050')
            ->whereDate('fecha', '2026-01-07')
            ->where('total', '>', 0)
            ->update([
                'tipo_comprobante' => 'nota_credito',
                'subtotal' => -1594328.76,
                'iva_importe' => -334809.04,
                'total' => -1929137.80,
                'observaciones' => 'Nota de crédito. Corregida: el importador la había cargado como "otro" en positivo.',
                'updated_at' => now(),
            ]);

        // ── 3. Comprobantes fuera de la presentación ──────────────────────
        DB::table('compras')
            ->where('id_empresa', $empresa->id)
            ->whereBetween('fecha', ['2026-01-01', '2026-01-31'])
            ->whereIn('numero_comprobante', self::NO_PRESENTADOS)
            ->update([
                'presentado_arca' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $empresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->first();
        if (! $empresa) {
            return;
        }

        DB::table('compras')
            ->where('id_empresa', $empresa->id)
            ->whereIn('numero_comprobante', array_column(self::COMPROBANTES_MERLO, 0))
            ->whereBetween('fecha', ['2026-01-01', '2026-01-31'])
            ->delete();

        DB::table('compras')
            ->where('id_empresa', $empresa->id)
            ->whereBetween('fecha', ['2026-01-01', '2026-01-31'])
            ->whereIn('numero_comprobante', self::NO_PRESENTADOS)
            ->update(['presentado_arca' => true, 'updated_at' => now()]);
    }
};
