<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conciliación de COMPRAS de WILMAR, enero a junio de 2026, contra los Libros
 * IVA Compras del contador. Mismo procedimiento que se aplicó a ELVIO, y los
 * mismos tres problemas.
 *
 *  1. Notas de crédito cargadas en positivo por el mapeo viejo del importador.
 *     Se reclasifican y se les da vuelta el signo.
 *  2. Comprobantes del ERP que no figuran en ningún libro: presentado_arca a
 *     false. El cruce se hace contra los seis meses juntos, porque el contador
 *     imputa algunos comprobantes al mes siguiente al de emisión.
 *  3. Comprobantes del libro que no estaban en el ERP: seguros y gastos
 *     bancarios, liquidaciones del grupo, tiques-factura y notas de crédito
 *     que ARCA no informa en Mis Comprobantes.
 *
 * Las liquidaciones primarias de granos que figuran en el libro de compras no
 * se tocan: en el ERP están cargadas como ventas.
 */
return new class extends Migration
{
    private const CUIT_WILMAR = '20-17520408-4';

    /** cuit => razón social, para los proveedores que haya que crear. */
    private const PROVEEDORES = [
        '30-71684948-8' => 'MERLO NATALIA',
        '30-67486012-5' => 'MAGNANO HILARIO, ELVIO Y WILMAR',
        '30-68195599-9' => 'DI TONDO',
        '34-50004533-9' => 'SAN CRISTOBAL SOCIEDAD MUTUAL DE SEGUROS GENERALES',
        '30-50001770-4' => 'LA SEGUNDA COOPERATIVA LTDA DE SEGUROS GENERALES',
        '30-50001091-2' => 'BANCO DE LA NACION ARGENTINA',
        '30-58511655-2' => 'HACIENDAS VILLA MARIA',
        '30-53204428-2' => 'COOPERATIVA AGRICOLA GANADERA DE EL TREBOL LTDA',
    ];

    /** [numero, fecha, cuit, tipo, iva, total] tal como figuran en el libro. */
    private const FALTANTES = [
        ['0004-00003847', '2026-04-01', '30-71684948-8', 'nota_credito', -1067.93, -6153.29], // N/C MERLO NATALIA
        ['0100-00000016', '2026-04-06', '30-67486012-5', 'liquidacion', 1365000.00, 14365000.00], // Liquida MAGNANO H, E
        ['0060-00008214', '2026-04-29', '30-68195599-9', 'ticket', 171119.09, 1114500.00], // TIQUE-F DI TONDO
        ['0004-00009796', '2026-01-14', '30-71684948-8', 'ticket', 5271.11, 30371.61], // TIQUE-F MERLO NATALIA
        ['0060-00007454', '2026-01-16', '30-68195599-9', 'ticket', 4107.19, 27195.00], // TIQUE-F DI TONDO
        ['0004-00009809', '2026-01-19', '30-71684948-8', 'ticket', 2064.26, 11894.06], // TIQUE-F MERLO NATALIA
        ['0060-00007544', '2026-01-31', '30-68195599-9', 'ticket', 153286.90, 1012154.66], // TIQUE-F DI TONDO
        ['0060-00007545', '2026-01-31', '30-68195599-9', 'ticket', 518458.75, 3489055.92], // TIQUE-F DI TONDO
        ['0001-00033678', '2026-02-01', '34-50004533-9', 'otro', 526546.25, 3312227.00], // Gastos SAN CRISTOBAL
        ['0060-00007663', '2026-02-23', '30-68195599-9', 'ticket', 4217.72, 28035.00], // TIQUE-F DI TONDO
        ['0060-00007721', '2026-02-28', '30-68195599-9', 'ticket', 174704.10, 1162845.99], // TIQUE-F DI TONDO
        ['0001-68515382', '2026-06-04', '30-50001770-4', 'otro', 42239.79, 273226.63], // Gastos LA SEGUNDA SE
        ['0100-00000021', '2026-06-28', '30-67486012-5', 'liquidacion', 6426000.00, 67626000.00], // Liquida MAGNANO H, E
        ['0001-00012026', '2026-03-01', '30-50001091-2', 'otro', 4612.02, 197144.72], // Gastos BANCO NACION
        ['0001-00022026', '2026-03-01', '30-50001091-2', 'otro', 4143.51, 96036.25], // Gastos BANCO NACION
        ['0001-67909570', '2026-03-04', '30-50001770-4', 'otro', 40023.40, 259080.50], // Gastos LA SEGUNDA SE
        ['0004-00010259', '2026-03-30', '30-71684948-8', 'nota_credito', -5339.63, -30766.46], // N/C MERLO NATALIA
        ['0001-00032026', '2026-03-31', '30-50001091-2', 'otro', 4410.00, 100398.69], // Gastos BANCO NACION
        ['0060-00007885', '2026-05-01', '30-68195599-9', 'ticket', 4591.72, 30420.00], // TIQUE-F DI TONDO
        ['0060-00007954', '2026-05-01', '30-68195599-9', 'ticket', 253611.70, 1664235.92], // TIQUE-F DI TONDO
        ['0060-00008089', '2026-05-01', '30-68195599-9', 'ticket', 126862.89, 859500.00], // TIQUE-F DI TONDO
        ['0060-00008090', '2026-05-01', '30-68195599-9', 'ticket', 337565.93, 2202353.99], // TIQUE-F DI TONDO
        ['0060-00008164', '2026-05-01', '30-68195599-9', 'ticket', 5271.55, 34230.00], // TIQUE-F DI TONDO
        ['0060-00008213', '2026-05-01', '30-68195599-9', 'ticket', 212849.54, 1387478.02], // TIQUE-F DI TONDO
        ['0060-00008250', '2026-05-01', '30-68195599-9', 'ticket', 27141.51, 174317.75], // TIQUE-F DI TONDO
        ['0001-30035469', '2026-05-01', '34-50004533-9', 'otro', 405116.71, 2548378.00], // Gastos SAN CRISTOBAL
        ['0005-00026564', '2026-05-15', '30-58511655-2', 'liquidacion', 5279022.50, 55580427.22], // Liquida HACIENDAS VIL
        ['0070-00008874', '2026-05-18', '30-68195599-9', 'ticket', 8301.94, 54032.94], // TIQUE-F DI TONDO
        ['0004-00051026', '2026-05-20', '30-53204428-2', 'factura_a', 180360.68, 1039221.06], // Factura COOP. AGRICOL
        ['0100-00000019', '2026-05-29', '30-67486012-5', 'liquidacion', 4704000.00, 49504000.00], // Liquida MAGNANO H, E
        ['0001-30036220', '2026-05-29', '34-50004533-9', 'otro', 647799.06, 4074965.01], // Gastos SAN CRISTOBAL
        ['0001-30036221', '2026-05-29', '34-50004533-9', 'otro', 1084868.30, 6824339.00], // Gastos SAN CRISTOBAL
        ['0001-00000380', '2026-05-31', '30-50001091-2', 'otro', 4410.00, 255718.70], // Gastos BANCO NACION
    ];

    /** [numero, fecha] de las notas de crédito cargadas en positivo. */
    private const NOTAS_CREDITO = [
            ['0006-00001051', '2026-01-07'], // SOLUCIONES AGROPECUARIAS S 1,929,136.69
            ['0005-00006031', '2026-03-20'], // SOLUCIONES AGROPECUARIAS S 533,174.73
            ['0003-00007370', '2026-04-30'], // COOPERATIVA AGRICOLA GANAD 5,516,901.36
            ['0003-00007368', '2026-04-30'], // COOPERATIVA AGRICOLA GANAD 1,661,299.88
            ['0003-00007369', '2026-04-30'], // COOPERATIVA AGRICOLA GANAD 16,279,575.00
            ['0014-00007916', '2026-05-05'], // CORTEVA SEEDS ARGENTINA S. 20,351.14
            ['0005-00006229', '2026-05-07'], // SOLUCIONES AGROPECUARIAS S 207,537.09
            ['0014-00008100', '2026-05-08'], // CORTEVA SEEDS ARGENTINA S. 179.19
    ];

    /** [numero, fecha] de los que no entraron en la presentación. */
    private const NO_PRESENTADOS = [
            ['1004-00144701', '2026-01-01'], // CANAL 2 CABLEVISION S A 49,140.00
            ['1004-00151248', '2026-02-01'], // CANAL 2 CABLEVISION S A 49,140.00
            ['0028-02254041', '2026-02-11'], // CAMARA DE CENTROS DE INSPE 7,699.50
            ['0002-00124286', '2026-02-11'], // RAFAELA REVISION TECNICA V 51,300.50
            ['1004-00153594', '2026-03-01'], // CANAL 2 CABLEVISION S A 51,570.00
            ['0100-00000002', '2026-03-16'], // RIBERO GISEL LORENA 286,431.74
            ['0100-00000008', '2026-03-16'], // RIBERO GISEL LORENA 286,431.74
            ['9645-00018679', '2026-03-20'], // ACEITERA GENERAL DEHEZA S. 26.00
            ['1004-00159706', '2026-04-01'], // CANAL 2 CABLEVISION S A 51,570.00
            ['0001-00001002', '2026-04-04'], // MEDINA NORMA BEATRIZ 1,732,236.00
            ['9645-00018805', '2026-04-10'], // ACEITERA GENERAL DEHEZA S. 26.00
            ['0001-00000079', '2026-04-11'], // MEDINA NORMA BEATRIZ 1,732,236.00
            ['0001-00001009', '2026-04-11'], // MEDINA NORMA BEATRIZ 1,732,236.00
            ['0001-00000080', '2026-04-11'], // MEDINA NORMA BEATRIZ 1,732,236.00
            ['9645-00018865', '2026-04-17'], // ACEITERA GENERAL DEHEZA S. 26.00
            ['1004-00162689', '2026-05-01'], // CANAL 2 CABLEVISION S A 54,720.00
            ['9645-00019007', '2026-05-08'], // ACEITERA GENERAL DEHEZA S. 26.00
            ['0003-00051026', '2026-05-20'], // COOPERATIVA AGRICOLA GANAD 1,039,221.06
            ['1004-00167294', '2026-06-01'], // CANAL 2 CABLEVISION S A 54,720.00
    ];

    public function up(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_WILMAR)->value('id');
        if (! $idEmpresa) {
            return;
        }

        // 1. Notas de crédito en positivo. El filtro total > 0 hace que
        //    correrla de nuevo no las vuelva a invertir.
        foreach (self::NOTAS_CREDITO as [$numero, $fecha]) {
            DB::table('compras')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->whereDate('fecha', $fecha)
                ->where('total', '>', 0)
                ->update([
                    'tipo_comprobante' => 'nota_credito',
                    'subtotal' => DB::raw('-ABS(subtotal)'),
                    'iva_importe' => DB::raw('-ABS(iva_importe)'),
                    'total' => DB::raw('-ABS(total)'),
                    'updated_at' => now(),
                ]);
        }

        // 2. Fuera de la presentación ante ARCA.
        foreach (self::NO_PRESENTADOS as [$numero, $fecha]) {
            DB::table('compras')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->whereDate('fecha', $fecha)
                ->update(['presentado_arca' => false, 'updated_at' => now()]);
        }

        // 3. Alta de los que faltaban.
        $proveedores = [];
        foreach (self::PROVEEDORES as $cuit => $razonSocial) {
            $id = DB::table('proveedores')
                ->where('id_empresa', $idEmpresa)->where('cuit', $cuit)->value('id');

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

        foreach (self::FALTANTES as [$numero, $fecha, $cuit, $tipo, $iva, $total]) {
            $yaEsta = DB::table('compras')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->whereDate('fecha', $fecha)
                ->exists();

            if ($yaEsta) {
                continue;
            }

            // El subtotal sale de total menos IVA: en los comprobantes de
            // seguros y banco parte del importe son conceptos exentos que el
            // libro informa en una línea aparte.
            $subtotal = round($total - $iva, 2);

            DB::table('compras')->insert([
                'id' => (string) Str::uuid(),
                'id_empresa' => $idEmpresa,
                'id_proveedor' => $proveedores[$cuit] ?? null,
                'id_establecimiento' => null,
                'tipo_comprobante' => $tipo,
                'numero_comprobante' => $numero,
                'fecha' => $fecha,
                'estado' => 'recibida',
                'subtotal' => $subtotal,
                'iva_porc' => abs($subtotal) > 0.005 ? round($iva / $subtotal * 100, 2) : 0,
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
    }

    public function down(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_WILMAR)->value('id');
        if (! $idEmpresa) {
            return;
        }

        foreach (self::FALTANTES as [$numero, $fecha]) {
            DB::table('compras')->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)->whereDate('fecha', $fecha)->delete();
        }

        foreach (self::NOTAS_CREDITO as [$numero, $fecha]) {
            DB::table('compras')->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)->whereDate('fecha', $fecha)
                ->where('total', '<', 0)
                ->update([
                    'tipo_comprobante' => 'otro',
                    'subtotal' => DB::raw('ABS(subtotal)'),
                    'iva_importe' => DB::raw('ABS(iva_importe)'),
                    'total' => DB::raw('ABS(total)'),
                    'updated_at' => now(),
                ]);
        }

        foreach (self::NO_PRESENTADOS as [$numero, $fecha]) {
            DB::table('compras')->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)->whereDate('fecha', $fecha)
                ->update(['presentado_arca' => true, 'updated_at' => now()]);
        }
    }
};
