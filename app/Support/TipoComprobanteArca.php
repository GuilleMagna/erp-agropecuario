<?php

namespace App\Support;

use App\Models\Compra;

/**
 * Tabla oficial de tipos de comprobante de ARCA y su equivalencia con los
 * tipos internos de Compra::TIPOS_COMPROBANTE.
 *
 * Es la única fuente de verdad del mapeo: la usan tanto la sincronización
 * automática (MrbotService) como la importación de archivo
 * (ImportarComprasArca), que antes tenían cada una su propia copia y se
 * desincronizaban.
 *
 * Los códigos son los de la tabla "Tipos de Comprobante" de ARCA. Lo que
 * importa para el ERP es la naturaleza fiscal:
 *
 *  - las notas de crédito se guardan en negativo (ver Compra::TIPOS_NEGATIVOS);
 *  - las liquidaciones y cuentas de venta y líquido producto son su propio
 *    tipo, no "otro": son los comprobantes del régimen de granos;
 *  - los comprobantes clase M se tratan como clase A (llevan IVA discriminado).
 */
class TipoComprobanteArca
{
    /** código ARCA => [etiqueta oficial, tipo interno del ERP] */
    public const CODIGOS = [
        1 => ['Factura A', 'factura_a'],
        2 => ['Nota de Débito A', 'nota_debito'],
        3 => ['Nota de Crédito A', 'nota_credito'],
        4 => ['Recibo A', 'recibo'],
        5 => ['Nota de Venta al contado A', 'factura_a'],
        6 => ['Factura B', 'factura_b'],
        7 => ['Nota de Débito B', 'nota_debito'],
        8 => ['Nota de Crédito B', 'nota_credito'],
        9 => ['Recibo B', 'recibo'],
        10 => ['Nota de Venta al contado B', 'factura_b'],
        11 => ['Factura C', 'factura_c'],
        12 => ['Nota de Débito C', 'nota_debito'],
        13 => ['Nota de Crédito C', 'nota_credito'],
        15 => ['Recibo C', 'recibo'],
        16 => ['Nota de Venta al contado C', 'factura_c'],
        17 => ['Liquidación de Servicios Públicos A', 'liquidacion'],
        18 => ['Liquidación de Servicios Públicos B', 'liquidacion'],
        19 => ['Factura de Exportación', 'factura_a'],
        20 => ['Nota de Débito por Operaciones con el Exterior', 'nota_debito'],
        21 => ['Nota de Crédito por Operaciones con el Exterior', 'nota_credito'],
        22 => ['Factura de Exportación Simplificada', 'factura_a'],
        23 => ['Comprobante A de Compra Primaria Sector Pesquero', 'factura_a'],
        24 => ['Comprobante A de Consignación Primaria Sector Pesquero', 'factura_a'],
        25 => ['Comprobante B de Compra Primaria Sector Pesquero', 'factura_b'],
        26 => ['Comprobante B de Consignación Primaria Sector Pesquero', 'factura_b'],
        27 => ['Liquidación Única Comercial Impositiva A', 'liquidacion'],
        28 => ['Liquidación Única Comercial Impositiva B', 'liquidacion'],
        29 => ['Liquidación Única Comercial Impositiva C', 'liquidacion'],
        30 => ['Comprobante de Compra de Bienes Usados', 'otro'],
        31 => ['Mandato / Consignación', 'otro'],
        32 => ['Comprobante para Recicladores de Materiales', 'otro'],
        33 => ['Liquidación Primaria de Granos', 'liquidacion'],
        34 => ['Comprobante A del Apartado A inciso f) RG 1415', 'factura_a'],
        35 => ['Comprobante B del Apartado A inciso f) RG 1415', 'factura_b'],
        36 => ['Comprobante C del Apartado A inciso f) RG 1415', 'factura_c'],
        37 => ['Nota de Débito o Documento Equivalente RG 1415', 'nota_debito'],
        38 => ['Nota de Crédito o Documento Equivalente RG 1415', 'nota_credito'],
        39 => ['Otro comprobante A que cumple con la RG 1415', 'factura_a'],
        40 => ['Otro comprobante B que cumple con la RG 1415', 'factura_b'],
        41 => ['Otro comprobante C que cumple con la RG 1415', 'factura_c'],
        43 => ['Nota de Crédito Liquidación Única Comercial Impositiva B', 'nota_credito'],
        44 => ['Nota de Crédito Liquidación Única Comercial Impositiva C', 'nota_credito'],
        45 => ['Nota de Débito Liquidación Única Comercial Impositiva A', 'nota_debito'],
        46 => ['Nota de Débito Liquidación Única Comercial Impositiva B', 'nota_debito'],
        47 => ['Nota de Débito Liquidación Única Comercial Impositiva C', 'nota_debito'],
        48 => ['Nota de Crédito Liquidación Única Comercial Impositiva A', 'nota_credito'],
        49 => ['Comprobante de Compra de Bienes no Registrables a Consumidor Final', 'otro'],
        50 => ['Recibo Factura A — Régimen de Factura de Crédito', 'recibo'],
        51 => ['Factura M', 'factura_a'],
        52 => ['Nota de Débito M', 'nota_debito'],
        53 => ['Nota de Crédito M', 'nota_credito'],
        54 => ['Recibo M', 'recibo'],
        55 => ['Nota de Venta al contado M', 'factura_a'],
        56 => ['Comprobante M del Apartado A inciso f) RG 1415', 'factura_a'],
        57 => ['Otro comprobante M que cumple con la RG 1415', 'factura_a'],
        58 => ['Cuenta de Venta y Líquido producto M', 'liquidacion'],
        59 => ['Liquidación M', 'liquidacion'],
        60 => ['Cuenta de Venta y Líquido producto A', 'liquidacion'],
        61 => ['Cuenta de Venta y Líquido producto B', 'liquidacion'],
        63 => ['Liquidación A', 'liquidacion'],
        64 => ['Liquidación B', 'liquidacion'],
        66 => ['Despacho de Importación', 'otro'],
        68 => ['Liquidación C', 'liquidacion'],
        70 => ['Recibo Factura de Crédito', 'recibo'],
        80 => ['Informe Diario de Cierre (ZETA)', 'otro'],
        81 => ['Tique Factura A', 'ticket'],
        82 => ['Tique Factura B', 'ticket'],
        83 => ['Tique', 'ticket'],
        88 => ['Remito Electrónico', 'remito'],
        89 => ['Resumen de datos', 'otro'],
        90 => ['Otro comprobante exceptuado de la RG 1415', 'otro'],
        91 => ['Remito', 'remito'],
        99 => ['Otro comprobante que no cumple con la RG 1415', 'otro'],
        110 => ['Tique Nota de Crédito', 'nota_credito'],
        111 => ['Tique Factura C', 'ticket'],
        112 => ['Tique Nota de Crédito A', 'nota_credito'],
        113 => ['Tique Nota de Crédito B', 'nota_credito'],
        114 => ['Tique Nota de Crédito C', 'nota_credito'],
        115 => ['Tique Nota de Débito A', 'nota_debito'],
        116 => ['Tique Nota de Débito B', 'nota_debito'],
        117 => ['Tique Nota de Débito C', 'nota_debito'],
        118 => ['Tique Factura M', 'ticket'],
        119 => ['Tique Nota de Crédito M', 'nota_credito'],
        120 => ['Tique Nota de Débito M', 'nota_debito'],
        201 => ['Factura de Crédito electrónica MiPyME A', 'factura_a'],
        202 => ['Nota de Débito electrónica MiPyME A', 'nota_debito'],
        203 => ['Nota de Crédito electrónica MiPyME A', 'nota_credito'],
        206 => ['Factura de Crédito electrónica MiPyME B', 'factura_b'],
        207 => ['Nota de Débito electrónica MiPyME B', 'nota_debito'],
        208 => ['Nota de Crédito electrónica MiPyME B', 'nota_credito'],
        211 => ['Factura de Crédito electrónica MiPyME C', 'factura_c'],
        212 => ['Nota de Débito electrónica MiPyME C', 'nota_debito'],
        213 => ['Nota de Crédito electrónica MiPyME C', 'nota_credito'],
        331 => ['Liquidación Secundaria de Granos', 'liquidacion'],
        332 => ['Certificación Electrónica (Granos)', 'liquidacion'],
    ];

    /**
     * Reglas por texto, para cuando el archivo trae la descripción en vez del
     * código. Se evalúan en orden y con la descripción normalizada (sin tildes
     * ni mayúsculas), así que las más específicas van primero: "tique nota de
     * credito" tiene que ganarle a "tique" y a "nota de credito".
     */
    private const TEXTOS = [
        'tique nota de credito' => 'nota_credito',
        'tique nota de debito' => 'nota_debito',
        'tique factura' => 'ticket',
        'nota de credito' => 'nota_credito',
        'nota de debito' => 'nota_debito',
        'nota credito' => 'nota_credito',
        'nota debito' => 'nota_debito',
        'liquidacion primaria de granos' => 'liquidacion',
        'liquidacion secundaria de granos' => 'liquidacion',
        'certificacion electronica' => 'liquidacion',
        'cuenta de venta y liquido producto' => 'liquidacion',
        'liquidacion unica comercial' => 'liquidacion',
        'liquidacion de servicios publicos' => 'liquidacion',
        'liquidacion' => 'liquidacion',
        'despacho de importacion' => 'otro',
        'factura de exportacion' => 'factura_a',
        'factura a' => 'factura_a',
        'factura b' => 'factura_b',
        'factura c' => 'factura_c',
        'factura m' => 'factura_a',
        'remito' => 'remito',
        'recibo' => 'recibo',
        'tique' => 'ticket',
        'ticket' => 'ticket',
    ];

    /**
     * Traduce lo que informa ARCA — un código, una descripción, o el clásico
     * "3 - Nota de Crédito A" — al tipo interno del ERP.
     */
    public static function mapear(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 'otro';
        }

        // "3 - Nota de Crédito A" o "003": si arranca con número, ese manda.
        if (preg_match('/^0*(\d{1,3})\b/', $raw, $m)) {
            $codigo = (int) $m[1];
            if (isset(self::CODIGOS[$codigo])) {
                return self::CODIGOS[$codigo][1];
            }
        }

        $texto = self::normalizar($raw);
        foreach (self::TEXTOS as $patron => $tipo) {
            if (str_contains($texto, $patron)) {
                return $tipo;
            }
        }

        return 'otro';
    }

    /** Etiqueta oficial del código, para mostrarle al usuario qué leyó ARCA. */
    public static function etiqueta(string $raw): ?string
    {
        if (preg_match('/^0*(\d{1,3})\b/', trim($raw), $m)) {
            return self::CODIGOS[(int) $m[1]][0] ?? null;
        }

        return null;
    }

    /** Los tipos internos a los que puede llegar a mapear. */
    public static function tiposInternos(): array
    {
        return array_values(array_unique(array_column(self::CODIGOS, 1)));
    }

    private static function normalizar(string $s): string
    {
        $s = mb_strtolower($s);
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);

        return preg_replace('/\s+/', ' ', $s);
    }
}
