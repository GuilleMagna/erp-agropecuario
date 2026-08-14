<?php

namespace Tests\Unit;

use App\Models\Compra;
use App\Support\TipoComprobanteArca;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TipoComprobanteArcaTest extends TestCase
{
    #[DataProvider('comprobantes')]
    public function test_mapea_el_tipo_que_informa_arca(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, TipoComprobanteArca::mapear($entrada));
    }

    public static function comprobantes(): array
    {
        return [
            // El código puede venir solo, con ceros o con la descripción al lado
            'código pelado' => ['1', 'factura_a'],
            'código con ceros' => ['001', 'factura_a'],
            'código y descripción' => ['1 - Factura A', 'factura_a'],
            'sólo descripción' => ['Factura A', 'factura_a'],

            // Notas de crédito y débito de todas las clases. Las de crédito se
            // guardan en negativo, así que confundirlas invierte el signo.
            'NC A' => ['3', 'nota_credito'],
            'NC B' => ['8', 'nota_credito'],
            'NC C' => ['13', 'nota_credito'],
            'NC M' => ['53', 'nota_credito'],
            'ND A' => ['2', 'nota_debito'],
            'NC MiPyME' => ['203', 'nota_credito'],
            'ND MiPyME' => ['212', 'nota_debito'],
            'NC por texto' => ['Nota de Crédito A', 'nota_credito'],
            'ND por texto' => ['Nota de Débito B', 'nota_debito'],

            // Liquidaciones: son las que aparecían como "otro"
            'Liquidación A' => ['63', 'liquidacion'],
            'Liquidación B' => ['64', 'liquidacion'],
            'Liquidación C' => ['68', 'liquidacion'],
            'Liquidación M' => ['59', 'liquidacion'],
            'Liquidación A por texto' => ['Liquidaciones A', 'liquidacion'],
            'cuenta de venta y líquido producto' => ['60', 'liquidacion'],
            'liquidación primaria de granos' => ['33', 'liquidacion'],
            'liquidación secundaria de granos' => ['331', 'liquidacion'],
            'certificación electrónica' => ['332', 'liquidacion'],
            'liquidación única comercial' => ['27', 'liquidacion'],
            'liquidación de servicios públicos' => ['17', 'liquidacion'],

            // Tiques: el 111 es Tique Factura C y el 118 Tique Factura M, no
            // notas de crédito.
            'tique factura A' => ['81', 'ticket'],
            'tique factura C' => ['111', 'ticket'],
            'tique factura M' => ['118', 'ticket'],
            'tique nota de crédito' => ['110', 'nota_credito'],
            'tique NC A' => ['112', 'nota_credito'],
            'tique ND A' => ['115', 'nota_debito'],
            'tique NC M' => ['119', 'nota_credito'],
            'tique ND M' => ['120', 'nota_debito'],
            'tique factura M por texto' => ['Tique Factura M', 'ticket'],

            'recibo' => ['4', 'recibo'],
            'remito' => ['91', 'remito'],
            'factura B' => ['6', 'factura_b'],
            'factura C' => ['11', 'factura_c'],
            'factura M se trata como A' => ['51', 'factura_a'],

            'despacho de importación' => ['66', 'otro'],
            'vacío' => ['', 'otro'],
            'desconocido' => ['no existe', 'otro'],
        ];
    }

    public function test_todo_lo_que_produce_la_tabla_existe_en_el_modelo(): void
    {
        $this->assertEmpty(
            array_diff(TipoComprobanteArca::tiposInternos(), array_keys(Compra::TIPOS_COMPROBANTE)),
            'La tabla mapea a un tipo que Compra::TIPOS_COMPROBANTE no declara.'
        );
    }

    public function test_devuelve_la_etiqueta_oficial_del_codigo(): void
    {
        $this->assertSame('Liquidación A', TipoComprobanteArca::etiqueta('63'));
        $this->assertSame('Nota de Crédito A', TipoComprobanteArca::etiqueta('3 - lo que sea'));
        $this->assertNull(TipoComprobanteArca::etiqueta('Factura A'));
    }
}
