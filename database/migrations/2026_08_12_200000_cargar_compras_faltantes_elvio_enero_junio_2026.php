<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alta de los comprobantes de ELVIO que figuran en los Libros IVA Compras de
 * enero a junio de 2026 y no estaban en el ERP.
 *
 * Son los que ARCA no informa en "Mis Comprobantes Recibidos" y por eso la
 * sincronización nunca los trajo:
 *
 *  - 14 de seguros y gastos bancarios (LA SEGUNDA, SAN CRISTÓBAL, BANCO NACIÓN).
 *  -  4 liquidaciones de MAGNANO H,E Y (30-67486012-5, la sociedad del grupo).
 *  -  2 tiques-factura de PRESA DUVAL.
 *  -  1 factura de MAURINO.
 *
 * Más la renumeración de una factura de ROSSO que en el ERP quedó con otro
 * número que en el libro.
 *
 * El subtotal se calcula como total − IVA: en los comprobantes de seguros y
 * banco parte del importe son conceptos exentos que el libro informa en una
 * línea aparte, y de esta forma el total siempre cierra y el crédito fiscal
 * queda exacto.
 */
return new class extends Migration
{
    private const CUIT_ELVIO = '20-13543013-8';

    /** cuit => razón social, para los proveedores que haya que crear. */
    private const PROVEEDORES = [
        '34-50004533-9' => 'SAN CRISTOBAL SOCIEDAD MUTUAL DE SEGUROS GENERALES',
        '30-50001770-4' => 'LA SEGUNDA COOPERATIVA LTDA DE SEGUROS GENERALES',
        '30-50001091-2' => 'BANCO DE LA NACION ARGENTINA',
        '30-67486012-5' => 'MAGNANO HILARIO, ELVIO Y WILMAR',
        '33-60712998-9' => 'PRESA DUVAL',
        '20-21651477-8' => 'MAURINO JORGE ALBERTO',
    ];

    /** [numero, fecha, cuit, tipo_comprobante, iva_importe, total] */
    private const COMPROBANTES = [
        // Seguros y gastos bancarios
        ['0001-00033677', '2026-02-01', '34-50004533-9', 'otro', 212745.96, 1338274.00],
        ['0001-00000378', '2026-03-01', '30-50001091-2', 'otro', 12582.57, 283155.61],
        ['0001-00000382', '2026-03-01', '30-50001091-2', 'otro', 13023.15, 346726.03],
        ['0001-05790015', '2026-03-03', '30-50001770-4', 'otro', 51770.07, 335121.36],
        ['0001-67917652', '2026-03-06', '30-50001770-4', 'otro', 49309.56, 319192.01],
        ['0001-00000387', '2026-03-31', '30-50001091-2', 'otro', 13023.15, 76898.60],
        ['0001-60492623', '2026-04-05', '30-50001770-4', 'otro', 98203.16, 573319.41],
        ['0001-68235616', '2026-04-22', '30-50001770-4', 'otro', 44852.93, 290130.09],
        ['0001-00000392', '2026-04-30', '30-50001091-2', 'otro', 13023.18, 548636.84],
        ['0001-30035468', '2026-05-01', '34-50004533-9', 'otro', 421321.38, 2650313.01],
        ['0001-68325574', '2026-05-04', '30-50001770-4', 'otro', 13875.37, 89752.65],
        ['0001-30036214', '2026-05-29', '34-50004533-9', 'otro', 1701448.13, 10702918.99],
        ['0001-00000397', '2026-05-31', '30-50001091-2', 'otro', 44731.68, 644819.93],
        ['0001-68527039', '2026-06-06', '30-50001770-4', 'otro', 43807.92, 283630.32],

        // Liquidaciones de la sociedad del grupo
        ['0100-00000017', '2026-04-06', '30-67486012-5', 'liquidacion', 1365000.00, 14365000.00],
        ['0005-00026563', '2026-05-15', '30-67486012-5', 'liquidacion', 5889139.35, 62001180.79],
        ['0100-00000018', '2026-05-29', '30-67486012-5', 'liquidacion', 4704000.00, 49504000.00],
        ['0100-00000020', '2026-06-28', '30-67486012-5', 'liquidacion', 6300000.00, 66300000.00],

        // Tiques-factura y factura sueltos
        ['0036-00031087', '2026-05-06', '33-60712998-9', 'ticket', 16933.76, 110001.19],
        ['0033-00062274', '2026-05-10', '33-60712998-9', 'ticket', 13829.76, 89799.04],
        ['0005-00009900', '2026-06-29', '20-21651477-8', 'factura_a', 4414.16, 25433.98],
    ];

    public function up(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        if (! $idEmpresa) {
            return;
        }

        // ── Proveedores ───────────────────────────────────────────────────
        $proveedores = [];
        foreach (self::PROVEEDORES as $cuit => $razonSocial) {
            $id = DB::table('proveedores')
                ->where('id_empresa', $idEmpresa)
                ->where('cuit', $cuit)
                ->value('id');

            if (! $id) {
                $id = (string) Str::uuid();
                DB::table('proveedores')->insert([
                    'id' => $id,
                    'id_empresa' => $idEmpresa,
                    'nombre' => $razonSocial,
                    'razon_social' => $razonSocial,
                    'cuit' => $cuit,
                    'rubro' => 'otro',
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $proveedores[$cuit] = $id;
        }

        // ── Comprobantes ──────────────────────────────────────────────────
        foreach (self::COMPROBANTES as [$numero, $fecha, $cuit, $tipo, $iva, $total]) {
            $yaEsta = DB::table('compras')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->whereDate('fecha', $fecha)
                ->exists();

            if ($yaEsta) {
                continue;
            }

            $subtotal = round($total - $iva, 2);

            DB::table('compras')->insert([
                'id' => (string) Str::uuid(),
                'id_empresa' => $idEmpresa,
                'id_proveedor' => $proveedores[$cuit],
                'id_establecimiento' => null,
                'tipo_comprobante' => $tipo,
                'numero_comprobante' => $numero,
                'fecha' => $fecha,
                'estado' => 'recibida',
                'subtotal' => $subtotal,
                'iva_porc' => $subtotal > 0 ? round($iva / $subtotal * 100, 2) : 0,
                'iva_importe' => $iva,
                'total' => $total,
                'stock_registrado' => false,
                'presentado_arca' => true,
                'actividad' => 'general',
                'observaciones' => 'Alta manual: figuraba en el Libro IVA Compras y ARCA no lo informa en Mis Comprobantes.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── Renumeración de la factura de ROSSO ───────────────────────────
        // El ERP la tenía como 0003-00003418 y el libro como 0003-00034189,
        // con el mismo importe y la misma fecha. Vale el número del libro.
        DB::table('compras')
            ->where('id_empresa', $idEmpresa)
            ->where('numero_comprobante', '0003-00003418')
            ->whereDate('fecha', '2026-06-25')
            ->update([
                'numero_comprobante' => '0003-00034189',
                'presentado_arca' => true,
                'observaciones' => 'Número corregido según el Libro IVA Compras de junio (el ERP lo tenía como 0003-00003418).',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        if (! $idEmpresa) {
            return;
        }

        foreach (self::COMPROBANTES as [$numero, $fecha]) {
            DB::table('compras')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->whereDate('fecha', $fecha)
                ->delete();
        }

        DB::table('compras')
            ->where('id_empresa', $idEmpresa)
            ->where('numero_comprobante', '0003-00034189')
            ->whereDate('fecha', '2026-06-25')
            ->update([
                'numero_comprobante' => '0003-00003418',
                'presentado_arca' => false,
                'updated_at' => now(),
            ]);
    }
};
