<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conciliación de COMPRAS de SOCIEDAD, enero a junio de 2026, contra los Libros
 * IVA Compras del contador. Mismo procedimiento que se aplicó a ELVIO y WILMAR, y los
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
    private const CUIT_SOCIEDAD = '30-67486012-5';

    /** cuit => razón social, para los proveedores que haya que crear. */
    private const PROVEEDORES = [
        '30-52320266-5' => 'SEGUIR SCA',
        '20-14608806-7' => 'SCOFANO ANGEL DOMINGO',
        '30-70798776-2' => 'ACEROS Y CONSTRUCCIONES SRL',
        '30-70825335-5' => 'AFLOVAC S.A',
        '20-16746310-0' => 'ZENON ABEL ALEJANDRO',
        '20-31883459-9' => 'CIMA MATIAS',
        '30-50001770-4' => 'LA SEGUNDA COOPERATIVA LTDA DE SEGUROS GENERALES',
        '33-60712998-9' => 'PRESA DUVAL',
        '30-66976185-2' => 'FUNDACION CORRENTINA PARA LA SANIDAD    ANIMAL',
        '20-13082353-0' => 'BREST LUIS ANGEL',
        '30-50001091-2' => 'BANCO DE LA NACION ARGENTINA',
        '30-67464649-2' => 'FUNDACION SAN MARTIN PARA LA ERRADICACION DE LA  AFTOSA',
        '20-27165635-2' => 'REPISO GUIDO RAFAEL',
        '30-68195599-9' => 'DI TONDO',
        '30-52076053-5' => 'COLOMBO Y MAGLIANO SA AGRICOLA GANADERA Y COMERCIAL',
        '30-58511655-2' => 'HACIENDAS VILLA MARIA',
        '30-52571862-6' => 'AGRICULTORES FEDERADOS ARGENTINOS SOC COOP LTDA',
    ];

    /** [numero, fecha, cuit, tipo, iva, total] tal como figuran en el libro. */
    private const FALTANTES = [
        ['0016-00009908', '2026-01-02', '30-52320266-5', 'ticket', 10762.21, 71004.04], // TIQUE-F SEGUIR SCA -
        ['0017-00012173', '2026-01-02', '30-52320266-5', 'ticket', 5128.57, 35043.04], // TIQUE-F SEGUIR SCA -
        ['0017-00012174', '2026-01-02', '30-52320266-5', 'ticket', 2915.70, 16800.00], // TIQUE-F SEGUIR SCA -
        ['0017-00012214', '2026-01-02', '30-52320266-5', 'ticket', 10788.88, 71001.95], // TIQUE-F SEGUIR SCA -
        ['0017-00012637', '2026-01-02', '30-52320266-5', 'ticket', 10680.62, 73218.92], // TIQUE-F SEGUIR SCA -
        ['0017-00012638', '2026-01-02', '30-52320266-5', 'ticket', 2915.70, 16800.00], // TIQUE-F SEGUIR SCA -
        ['0018-00008336', '2026-01-02', '30-52320266-5', 'ticket', 1314.73, 9004.99], // TIQUE-F SEGUIR SCA -
        ['0003-00002113', '2026-01-02', '20-14608806-7', 'factura_a', 4373.55, 25200.00], // Factura MADERERA LOS
        ['0004-00019170', '2026-01-02', '30-70798776-2', 'factura_a', 2960.77, 17059.66], // Factura ACEROS Y CONS
        ['0007-00001172', '2026-01-02', '30-70825335-5', 'factura_a', 93798.54, 633200.00], // Factura AFLOVAC S.A
        ['0008-00011816', '2026-01-02', '20-16746310-0', 'factura_a', 39136.36, 225500.00], // Factura AGROVETERINAR
        ['0008-00012439', '2026-01-02', '20-16746310-0', 'factura_a', 28490.58, 164160.00], // Factura AGROVETERINAR
        ['0002-00000207', '2026-01-02', '20-31883459-9', 'factura_c', 0.00, 46000.47], // Factura CIMA MATIAS
        ['0002-00000209', '2026-01-02', '20-31883459-9', 'factura_c', 0.00, 22500.34], // Factura CIMA MATIAS
        ['0002-00000213', '2026-01-04', '20-31883459-9', 'factura_c', 0.00, 110000.55], // Factura CIMA MATIAS
        ['0001-67542138', '2026-01-04', '30-50001770-4', 'otro', 33979.09, 219954.20], // Gastos COOP.DE SEGUR
        ['0036-00026783', '2026-01-07', '33-60712998-9', 'ticket', 11350.24, 74010.44], // TIQUE-F EMPRESA DUVAL
        ['0001-40412120', '2026-01-07', '30-50001770-4', 'otro', 52988.14, 338830.86], // Gastos COOP.DE SEGUR
        ['0036-00026906', '2026-01-11', '33-60712998-9', 'ticket', 19171.02, 125006.61], // TIQUE-F EMPRESA DUVAL
        ['0001-60467480', '2026-02-11', '30-50001770-4', 'otro', 260693.05, 1524488.88], // Gastos COOP.DE SEGUR
        ['0001-40439270', '2026-06-23', '30-50001770-4', 'otro', 42209.72, 269707.30], // Gastos COOP.DE SEGUR
        ['0001-40439271', '2026-06-23', '30-50001770-4', 'otro', 22819.96, 145813.08], // Gastos COOP.DE SEGUR
        ['0009-00119113', '2026-06-24', '30-66976185-2', 'factura_a', 0.00, 76353.00], // Factura FUCOSA
        ['0009-00119124', '2026-06-24', '30-66976185-2', 'factura_a', 0.00, 172410.00], // Factura FUCOSA
        ['0004-00000139', '2026-06-30', '20-13082353-0', 'factura_a', 1117200.00, 6437200.00], // Factura BREST LUIS
        ['0001-00001037', '2026-03-01', '30-50001091-2', 'otro', 37013.68, 688458.04], // Gastos BANCO NACION
        ['0001-00001043', '2026-03-01', '30-50001091-2', 'otro', 11016.39, 1001522.25], // Gastos BANCO NACION
        ['0001-13303206', '2026-03-13', '30-67464649-2', 'otro', 0.00, 218400.00], // Gastos FU.S.MA.
        ['0001-68041394', '2026-03-22', '30-50001770-4', 'otro', 27294.71, 176685.10], // Gastos COOP.DE SEGUR
        ['0004-00055521', '2026-03-30', '20-27165635-2', 'factura_a', 2950.41, 17000.00], // Factura REPISO GUIDO
        ['0001-00001049', '2026-03-31', '30-50001091-2', 'otro', 11757.48, 989857.15], // Gastos BANCO NACION
        ['0055-00016041', '2026-05-01', '30-68195599-9', 'ticket', 16198.23, 108001.08], // TIQUE-F EST.DE SERV.d
        ['0001-68421153', '2026-05-01', '30-50001770-4', 'otro', 71227.72, 460734.41], // Gastos COOP.DE SEGUR
        ['1041-00001840', '2026-05-11', '30-52076053-5', 'liquidacion', 9630519.58, 101349753.68], // Liquida COLOMBO Y MAG
        ['1041-00001841', '2026-05-11', '30-52076053-5', 'liquidacion', 8949628.79, 94184188.69], // Liquida COLOMBO Y MAG
        ['0005-00026571', '2026-05-15', '30-58511655-2', 'liquidacion', 13775037.41, 144990869.85], // Liquida HACIENDAS VIL
        ['0001-00001061', '2026-05-31', '30-50001091-2', 'otro', 12901.35, 801179.41], // Gastos BANCO NACION
        ['0037-00017270', '2026-04-01', '33-60712998-9', 'ticket', 10632.86, 70001.14], // TIQUE-F RICARDO TOLEN
        ['0037-00017476', '2026-04-01', '33-60712998-9', 'ticket', 17499.38, 115006.60], // TIQUE-F RICARDO TOLEN
        ['3320-22438909', '2026-04-15', '30-52571862-6', 'liquidacion', 346011.96, 1993687.96], // Cert.De AGRIC.FEDERS.
        ['0001-68228655', '2026-04-20', '30-50001770-4', 'otro', 45817.18, 296367.13], // Gastos COOP.DE SEGUR
        ['0001-68247478', '2026-04-24', '30-50001770-4', 'otro', 35678.44, 230785.04], // Gastos COOP.DE SEGUR
        ['0001-00001056', '2026-04-30', '30-50001091-2', 'otro', 17657.22, 315507.17], // Gastos BANCO NACION
    ];

    /** [numero, fecha] de las notas de crédito cargadas en positivo. */
    private const NOTAS_CREDITO = [
            ['1000-00050317', '2026-01-20'], // JOSE Y PEDRO CELOTTI S.A.C 8,402.55
            ['0005-00005836', '2026-01-21'], // SOLUCIONES AGROPECUARIAS S 165,772.19
            ['0005-00005835', '2026-01-21'], // SOLUCIONES AGROPECUARIAS S 284,933.14
            ['0005-00005838', '2026-01-21'], // SOLUCIONES AGROPECUARIAS S 284,933.14
            ['1000-00050391', '2026-01-23'], // JOSE Y PEDRO CELOTTI S.A.C 8,713.04
            ['0004-00000104', '2026-01-28'], // ATP SA 2,250,600.00
            ['1000-00050612', '2026-02-06'], // JOSE Y PEDRO CELOTTI S.A.C 31,141.11
            ['0005-00003664', '2026-03-26'], // BRUNO SILVIO JOSE 148,930.60
            ['0005-00006142', '2026-04-15'], // SOLUCIONES AGROPECUARIAS S 1,173,327.54
            ['0005-00006345', '2026-05-28'], // SOLUCIONES AGROPECUARIAS S 1,118,447.25
    ];

    /** [numero, fecha] de los que no entraron en la presentación. */
    private const NO_PRESENTADOS = [
            ['3950-06018642', '2026-01-14'], // TELECOM ARGENTINA SOCIEDAD 199,761.38
            ['0016-00010125', '2026-01-15'], // SEGUIR SCA 34,963.93
            ['8930-00047637', '2026-01-25'], // TELECOM ARGENTINA SOCIEDAD 6,148.76
            ['4980-00033852', '2026-01-27'], // TELECOM ARGENTINA SOCIEDAD 1,229.75
            ['0017-00013442', '2026-02-06'], // SEGUIR SCA 107,100.00
            ['0004-00021384', '2026-02-07'], // ACEROS Y CONSTRUCCIONES SR 57,310.00
            ['3950-06299450', '2026-02-14'], // TELECOM ARGENTINA SOCIEDAD 194,728.60
            ['0018-00009385', '2026-02-16'], // SEGUIR SCA 35,005.94
            ['0002-00000438', '2026-02-19'], // TRIBOLO JORGE ALBERTO 130,000.00
            ['8930-00054766', '2026-02-22'], // TELECOM ARGENTINA SOCIEDAD 6,148.76
            ['0012-00032815', '2026-02-23'], // PELICANO SRL 17,410.00
            ['4980-00042769', '2026-02-23'], // TELECOM ARGENTINA SOCIEDAD 1,229.75
            ['0017-00014183', '2026-03-09'], // SEGUIR SCA 112,160.00
            ['0009-00001155', '2026-03-13'], // LUIS PIASENTINI SOCIEDAD A 390,687.20
            ['0009-00016044', '2026-03-13'], // LUIS PIASENTINI SOCIEDAD A 390,687.20
            ['3950-06575557', '2026-03-14'], // TELECOM ARGENTINA SOCIEDAD 200,490.94
            ['0017-00014328', '2026-03-14'], // SEGUIR SCA 16,800.00
            ['0017-00014327', '2026-03-14'], // SEGUIR SCA 72,243.97
            ['8930-00063939', '2026-03-29'], // TELECOM ARGENTINA SOCIEDAD 3,586.79
            ['8930-00064012', '2026-03-30'], // TELECOM ARGENTINA SOCIEDAD 12,297.53
            ['4980-00053260', '2026-03-30'], // TELECOM ARGENTINA SOCIEDAD 717.36
            ['8930-00064011', '2026-03-30'], // TELECOM ARGENTINA SOCIEDAD 7,173.56
            ['0004-00005521', '2026-03-30'], // REPISO GUIDO RAFAEL 17,000.00
            ['4980-00053494', '2026-03-31'], // TELECOM ARGENTINA SOCIEDAD 1,434.71
            ['0007-00002076', '2026-04-07'], // RODAMIENTOS GOYA S.H. DE G 98,800.00
            ['0012-00034493', '2026-04-09'], // PELICANO SRL 89,200.00
            ['0002-00011565', '2026-04-13'], // FUNDACION SAN MARTIN PARA  218,400.00
            ['3950-06855073', '2026-04-14'], // TELECOM ARGENTINA SOCIEDAD 213,634.30
            ['0011-00002840', '2026-04-16'], // PELICANO SRL 5,600.00
            ['1320-00426286', '2026-04-20'], // AGRICULTORES FEDERADOS ARG 150,674.04
            ['1320-00426285', '2026-04-20'], // AGRICULTORES FEDERADOS ARG 3,118.27
            ['0015-00025110', '2026-04-25'], // PELICANO SRL 58,860.00
            ['0018-00010457', '2026-05-06'], // SEGUIR SCA 90,000.00
            ['0018-00010470', '2026-05-07'], // SEGUIR SCA 139,001.08
            ['0017-00015615', '2026-05-08'], // SEGUIR SCA 20,000.00
            ['0010-00018968', '2026-05-12'], // PELICANO SRL 100,220.00
            ['3950-07132215', '2026-05-14'], // TELECOM ARGENTINA SOCIEDAD 214,727.88
            ['0015-00025831', '2026-05-16'], // PELICANO SRL 45,780.00
            ['8930-00078584', '2026-05-22'], // TELECOM ARGENTINA SOCIEDAD 7,173.56
            ['0018-00010693', '2026-05-22'], // SEGUIR SCA 132,990.99
            ['0018-00010694', '2026-05-22'], // SEGUIR SCA 12,000.00
            ['4980-00071616', '2026-05-23'], // TELECOM ARGENTINA SOCIEDAD 1,434.71
            ['1320-00432587', '2026-05-29'], // AGRICULTORES FEDERADOS ARG 14,893.58
            ['1320-00432590', '2026-05-29'], // AGRICULTORES FEDERADOS ARG 1,280,375.64
            ['1320-00432586', '2026-05-29'], // AGRICULTORES FEDERADOS ARG 1,010,024.64
            ['1320-00432585', '2026-05-29'], // AGRICULTORES FEDERADOS ARG 22,057.40
            ['1320-00432588', '2026-05-29'], // AGRICULTORES FEDERADOS ARG 681,987.60
            ['1320-00432589', '2026-05-29'], // AGRICULTORES FEDERADOS ARG 27,961.47
            ['0002-00000228', '2026-06-01'], // AYALA HECTOR MARCELO 480,000.00
            ['0105-00066348', '2026-06-05'], // COLOMBO Y MAGLIANO SA AGRI 437,066.01
            ['0009-00001515', '2026-06-11'], // LAS PRADERAS SEMILLAS FORR 92,095.71
            ['3950-07420950', '2026-06-14'], // TELECOM ARGENTINA SOCIEDAD 257,872.46
            ['0004-00022649', '2026-06-24'], // ACEROS Y CONSTRUCCIONES SR 87,469.96
            ['0003-00002303', '2026-06-25'], // SCOFANO ANGEL DOMINGO 280,200.00
            ['0018-00011194', '2026-06-26'], // SEGUIR SCA 91,760.00
            ['0004-00001339', '2026-06-30'], // BREST LUIS ANGEL 6,437,200.00
    ];

    public function up(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_SOCIEDAD)->value('id');
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
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_SOCIEDAD)->value('id');
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
