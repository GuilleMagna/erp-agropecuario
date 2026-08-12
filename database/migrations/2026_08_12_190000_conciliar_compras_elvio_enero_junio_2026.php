<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Conciliación de COMPRAS de ELVIO, enero a junio de 2026, contra los Libros
 * IVA Compras que presentó el contador ante ARCA.
 *
 * Dos correcciones, ambas idempotentes:
 *
 *  1. Notas de crédito cargadas en positivo. El importador de ARCA mapeaba
 *     los códigos 3/8/13 a tipo "otro" sin invertir el signo, así que sumaban
 *     en vez de restar (ver MrbotService::MAPA_TIPOS, ya corregido). Se
 *     reclasifican a nota_credito y se les da vuelta el signo. El importe
 *     absoluto del ERP se respeta: sólo cambia el signo.
 *
 *  2. Comprobantes que están en el ERP y no figuran en NINGUNO de los seis
 *     libros. Se marcan presentado_arca = false. El cruce se hace contra los
 *     seis meses juntos y no mes a mes, porque el contador imputa algunos
 *     comprobantes al mes siguiente al de emisión.
 *
 * Las liquidaciones primarias de granos que figuran en los libros de compras
 * no se tocan: en el ERP están cargadas como ventas.
 */
return new class extends Migration
{
    private const CUIT_ELVIO = '20-13543013-8';

    /** [numero_comprobante, fecha] de las notas de crédito a dar vuelta. */
    private const NOTAS_CREDITO = [
            ['0002-00004542', '2026-02-04'], // CORSALINI MARIO DOMINGO 6,475.90
            ['0017-00000555', '2026-02-23'], // PEDRO BRAVIN S. A. 103,396.36
            ['0002-00004658', '2026-03-03'], // CORSALINI MARIO DOMINGO 27,994.76
            ['0005-00006030', '2026-03-20'], // SOLUCIONES AGROPECUARIAS S 427,747.95
            ['0002-00004870', '2026-04-23'], // CORSALINI MARIO DOMINGO 18,060.49
            ['0018-00009535', '2026-04-29'], // EPPLE JAVIER OTTMAR JUAN 36,000.90
            ['0018-00009534', '2026-04-29'], // EPPLE JAVIER OTTMAR JUAN 102,075.00
            ['0003-00007372', '2026-04-30'], // COOPERATIVA AGRICOLA GANAD 12,685,000.00
            ['0003-00007371', '2026-04-30'], // COOPERATIVA AGRICOLA GANAD 1,661,299.88
            ['0003-00007373', '2026-04-30'], // COOPERATIVA AGRICOLA GANAD 13,713,278.17
            ['0018-00009562', '2026-05-04'], // EPPLE JAVIER OTTMAR JUAN 73,179.00
            ['0014-00007912', '2026-05-05'], // CORTEVA SEEDS ARGENTINA S. 19,459.63
            ['0005-00006231', '2026-05-07'], // SOLUCIONES AGROPECUARIAS S 123,963.53
            ['0014-00008097', '2026-05-08'], // CORTEVA SEEDS ARGENTINA S. 171.34
            ['0003-00007556', '2026-05-29'], // COOPERATIVA AGRICOLA GANAD 1,069,438.00
            ['0002-00005005', '2026-06-01'], // CORSALINI MARIO DOMINGO 18,013.57
            ['0002-00000083', '2026-06-19'], // CORSALINI AGRO INDUSTRIAL  11,138.67
            ['0017-00001006', '2026-01-13'], // SOLDINI STOCK SA — no figura en el libro, N/C confirmada aparte
    ];

    /** [numero_comprobante, fecha] de los que no entraron en la presentación. */
    private const NO_PRESENTADOS = [
            ['0031-05775879', '2026-01-01'], // MUTUAL FEDERADA 25 DE JUNI 594,958.50
            ['0018-00005225', '2026-01-09'], // MAC ENERGIA S. R. L. 40,000.04
            ['0017-00001006', '2026-01-13'], // SOLDINI STOCK SA 24,563.00
            ['0018-00005590', '2026-01-19'], // MAC ENERGIA S. R. L. 40,000.00
            ['0041-01306206', '2026-01-22'], // AUTOPISTA PROVINCIAL AP 01 700.00
            ['0007-00079068', '2026-01-23'], // MAGNANO MARIA PAULA 42,608.49
            ['9095-00164970', '2026-01-27'], // MUTUAL FEDERADA 25 DE JUNI 13,018.01
            ['0005-00009167', '2026-01-30'], // MAURINO JORGE ALBERTO 58,135.04
            ['0002-00040446', '2026-01-30'], // PAMPA HERRAMIENTAS S.R.L 777,157.00
            ['1996-00014224', '2026-01-30'], // NUEVA LA S.R.L. 44,180.00
            ['0002-00275862', '2026-01-31'], // M Y P PRODUCCIONES MARKETI 39,990.00
            ['0002-00225973', '2026-01-31'], // PROYECTO DOSMILVEINTIUNO S 39,990.00
            ['0004-08016999', '2026-01-31'], // FIRST LABEL S.R.L. 247,417.00
            ['0031-05835717', '2026-02-01'], // MUTUAL FEDERADA 25 DE JUNI 611,581.50
            ['0017-00008030', '2026-02-05'], // MAC ENERGIA S. R. L. 118,131.96
            ['0004-00078464', '2026-02-12'], // WALSH ADRIAN LEONARDO 45,779.00
            ['0104-12418629', '2026-02-14'], // CORREDORES VIALES SOCIEDAD 6,291.90
            ['0050-40580729', '2026-02-15'], // AUTOPISTAS DE BUENOS AIRES 6,964.13
            ['0018-00006993', '2026-02-22'], // MAC ENERGIA S. R. L. 121,014.97
            ['0013-00003793', '2026-02-24'], // CASTILLEJOS SILVANA ALEJAN 1,399,000.00
            ['0104-12687158', '2026-02-27'], // CORREDORES VIALES SOCIEDAD 3,775.14
            ['0002-00279911', '2026-02-28'], // M Y P PRODUCCIONES MARKETI 39,990.00
            ['0002-00232873', '2026-02-28'], // PROYECTO DOSMILVEINTIUNO S 39,990.00
            ['0107-00000040', '2026-02-28'], // GRUPO VENETO S.A.S. 136,146.44
            ['0050-40904107', '2026-02-28'], // AUTOPISTAS DE BUENOS AIRES 9,889.06
            ['0107-00000070', '2026-02-28'], // GRUPO VENETO S.A.S. 37,122.99
            ['0107-00000041', '2026-02-28'], // GRUPO VENETO S.A.S. 29,119.17
            ['0107-00000071', '2026-02-28'], // GRUPO VENETO S.A.S. 9,706.39
            ['0031-05895407', '2026-03-01'], // MUTUAL FEDERADA 25 DE JUNI 629,215.00
            ['0003-00003288', '2026-03-03'], // ROSSO HUGO DANIEL 118,000.41
            ['0107-00000118', '2026-03-06'], // GRUPO VENETO S.A.S. 145,249.15
            ['0107-00000119', '2026-03-06'], // GRUPO VENETO S.A.S. 29,119.17
            ['0107-00000148', '2026-03-06'], // GRUPO VENETO S.A.S. 39,154.67
            ['0107-00000149', '2026-03-06'], // GRUPO VENETO S.A.S. 9,706.39
            ['0014-00003273', '2026-03-07'], // POZZI FABRICIO CRISTIAN HU 57,800.00
            ['0003-00065812', '2026-03-10'], // RIOS MURILLO JUAN SEBASTIA 110,201.85
            ['0007-00051408', '2026-03-10'], // EMPRENDIMIENTOS XYNCO S.R. 101,196.26
            ['0007-00034553', '2026-03-11'], // COMPETITION IMPORT GROUP 71,391.00
            ['0013-00003832', '2026-03-13'], // CASTILLEJOS SILVANA ALEJAN 79,490.01
            ['0104-12818607', '2026-03-14'], // CORREDORES VIALES SOCIEDAD 1,500.00
            ['0104-12867785', '2026-03-14'], // CORREDORES VIALES SOCIEDAD 1,500.00
            ['9095-00168632', '2026-03-18'], // MUTUAL FEDERADA 25 DE JUNI 13,770.62
            ['9645-00018673', '2026-03-20'], // ACEITERA GENERAL DEHEZA S. 26.00
            ['0006-00003958', '2026-03-23'], // TBCIN S.A 68,832.26
            ['0017-00010862', '2026-03-28'], // MAC ENERGIA S. R. L. 100,000.04
            ['0104-13072460', '2026-03-29'], // CORREDORES VIALES SOCIEDAD 1,500.00
            ['0007-00082054', '2026-03-30'], // MAGNANO MARIA PAULA 20,500.77
            ['0041-01511708', '2026-03-30'], // AUTOPISTA PROVINCIAL AP 01 1,680.00
            ['0031-05954915', '2026-04-01'], // MUTUAL FEDERADA 25 DE JUNI 647,331.00
            ['0017-00011205', '2026-04-03'], // MAC ENERGIA S. R. L. 130,005.02
            ['0001-00001001', '2026-04-04'], // MEDINA NORMA BEATRIZ 1,032,662.40
            ['0007-00000851', '2026-04-08'], // COOPERATIVA DE PROVISION D 8,700.00
            ['0007-00002472', '2026-04-08'], // COOPERATIVA DE PROVISION D 8,700.00
            ['0107-00000226', '2026-04-09'], // GRUPO VENETO S.A.S. 39,560.34
            ['0107-00000196', '2026-04-09'], // GRUPO VENETO S.A.S. 146,151.60
            ['0107-00000227', '2026-04-09'], // GRUPO VENETO S.A.S. 10,272.52
            ['0107-00000197', '2026-04-09'], // GRUPO VENETO S.A.S. 30,817.56
            ['9645-00018797', '2026-04-10'], // ACEITERA GENERAL DEHEZA S. 26.00
            ['9645-00018798', '2026-04-10'], // ACEITERA GENERAL DEHEZA S. 26.00
            ['0018-00039852', '2026-04-10'], // EPPLE JAVIER OTTMAR JUAN 254,751.92
            ['0009-00045183', '2026-04-10'], // HURA S.A.S 83,233.00
            ['0001-00000078', '2026-04-11'], // MEDINA NORMA BEATRIZ 1,032,662.40
            ['0104-13358788', '2026-04-15'], // CORREDORES VIALES SOCIEDAD 7,500.00
            ['9645-00018859', '2026-04-17'], // ACEITERA GENERAL DEHEZA S. 26.00
            ['0007-00083229', '2026-04-20'], // MAGNANO MARIA PAULA 30,159.42
            ['0002-00027610', '2026-04-24'], // M Y P PRODUCCIONES MARKETI 39,990.00
            ['0007-00083520', '2026-04-27'], // MAGNANO MARIA PAULA 20,162.92
            ['0003-00007536', '2026-04-27'], // MARTIN MIGUEL ANGEL 129,544.20
            ['0003-00007535', '2026-04-27'], // MARTIN MIGUEL ANGEL 208,010.24
            ['0002-00028035', '2026-04-28'], // M Y P PRODUCCIONES MARKETI 39,990.00
            ['0018-00040253', '2026-04-28'], // EPPLE JAVIER OTTMAR JUAN 177,083.90
            ['0104-13521693', '2026-04-29'], // CORREDORES VIALES SOCIEDAD 3,000.00
            ['0002-00026491', '2026-04-30'], // PROYECTO DOSMILVEINTIUNO S 39,990.00
            ['0002-00026492', '2026-04-30'], // PROYECTO DOSMILVEINTIUNO S 39,990.00
            ['0031-06014327', '2026-05-01'], // MUTUAL FEDERADA 25 DE JUNI 668,980.00
            ['0017-00012793', '2026-05-02'], // MAC ENERGIA S. R. L. 160,999.00
            ['9095-00172064', '2026-05-05'], // MUTUAL FEDERADA 25 DE JUNI 14,524.23
            ['0002-00000372', '2026-05-06'], // LEDESMA LUIS ALBERTO 1,063,147.09
            ['0107-00000305', '2026-05-08'], // GRUPO VENETO S.A.S. 10,272.52
            ['0107-00000304', '2026-05-08'], // GRUPO VENETO S.A.S. 50,890.06
            ['0107-00000274', '2026-05-08'], // GRUPO VENETO S.A.S. 187,931.61
            ['0107-00000275', '2026-05-08'], // GRUPO VENETO S.A.S. 30,817.56
            ['0104-13609783', '2026-05-14'], // CORREDORES VIALES SOCIEDAD 1,500.00
            ['0002-00000027', '2026-05-14'], // LEDESMA LUIS ALBERTO 1,063,147.09
            ['1002-00000392', '2026-05-19'], // ASOCIACION ASOCIACION BOMB 100,000.00
            ['0003-00024539', '2026-05-21'], // LARESE DANIEL DAMIAN 192,225.08
            ['0041-01722005', '2026-05-22'], // AUTOPISTA PROVINCIAL AP 01 1,680.00
            ['0007-00085164', '2026-05-28'], // MAGNANO MARIA PAULA 52,842.98
            ['0007-00085163', '2026-05-28'], // MAGNANO MARIA PAULA 68,050.00
            ['0104-13867892', '2026-05-29'], // CORREDORES VIALES SOCIEDAD 1,500.00
            ['0003-00000560', '2026-06-01'], // MEDELINOX S.A 510,208.60
            ['0031-06073526', '2026-06-01'], // MUTUAL FEDERADA 25 DE JUNI 686,307.50
            ['0002-00005004', '2026-06-01'], // CORSALINI MARIO DOMINGO 18,013.57
            ['0003-00000034', '2026-06-02'], // MEDELINOX S.A 191,373.60
            ['0005-00034442', '2026-06-03'], // CETIMA S.A. 59,888.00
            ['0107-00000382', '2026-06-05'], // GRUPO VENETO S.A.S. 42,988.41
            ['0107-00000352', '2026-06-05'], // GRUPO VENETO S.A.S. 179,052.40
            ['0107-00000353', '2026-06-05'], // GRUPO VENETO S.A.S. 32,700.76
            ['0107-00000383', '2026-06-05'], // GRUPO VENETO S.A.S. 10,900.25
            ['0007-00004080', '2026-06-11'], // COOPERATIVA DE PROVISION D 9,200.00
            ['0007-00005680', '2026-06-11'], // COOPERATIVA DE PROVISION D 9,200.00
            ['0104-14088129', '2026-06-14'], // CORREDORES VIALES SOCIEDAD 3,000.00
            ['0003-00010945', '2026-06-22'], // CALCATERRA MARTIN 31,018.29
            ['0018-00118617', '2026-06-24'], // ESPACIO MARKETING S.R.L. 384,484.43
            ['0003-00003418', '2026-06-25'], // ROSSO HUGO DANIEL 215,622.00
            ['0100-00000101', '2026-06-29'], // MAGNANO CAMILA REGINA 3,000,000.00
            ['0100-00000103', '2026-06-30'], // MAGNANO CAMILA REGINA 3,000,000.00
            ['9095-00176156', '2026-06-30'], // MUTUAL FEDERADA 25 DE JUNI 39,230.57
    ];

    public function up(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        if (! $idEmpresa) {
            return;
        }

        // 1. Notas de crédito: reclasificar y dar vuelta el signo. El filtro
        //    total > 0 hace que correrla de nuevo no las vuelva a invertir.
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
        foreach (array_chunk(self::NO_PRESENTADOS, 50) as $chunk) {
            foreach ($chunk as [$numero, $fecha]) {
                DB::table('compras')
                    ->where('id_empresa', $idEmpresa)
                    ->where('numero_comprobante', $numero)
                    ->whereDate('fecha', $fecha)
                    ->update(['presentado_arca' => false, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        $idEmpresa = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        if (! $idEmpresa) {
            return;
        }

        foreach (self::NOTAS_CREDITO as [$numero, $fecha]) {
            DB::table('compras')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->whereDate('fecha', $fecha)
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
            DB::table('compras')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->whereDate('fecha', $fecha)
                ->update(['presentado_arca' => true, 'updated_at' => now()]);
        }
    }
};
