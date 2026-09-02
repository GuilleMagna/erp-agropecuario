<?php

namespace Tests\Unit;

use App\Livewire\Ventas\GestionVentasGranos;
use App\Models\VentaGrano;
use Tests\TestCase;

class GestionVentasGranosCalculosTest extends TestCase
{
    public function test_el_flete_se_registra_pero_no_se_descuenta_dos_veces(): void
    {
        $componente = new GestionVentasGranos();
        $componente->cantidad_kg = '12600';
        $componente->factor = '100';
        $componente->precio_kg = '496.56';
        $componente->flete_tn = '3390.90';

        $this->assertEqualsWithDelta(6256656.00, $componente->subtotalCalculado(), 0.001);
    }

    public function test_el_precio_por_kg_se_copia_de_la_seccion_operacion(): void
    {
        $componente = new GestionVentasGranos();
        $componente->cantidad_kg = '15000';
        $componente->factor = '100';
        $componente->precio_kg = '518.39';
        $componente->flete_tn = '1356.10';

        $this->assertEqualsWithDelta(7775850.00, $componente->subtotalCalculado(), 0.001);
    }

    public function test_el_total_operacion_no_descuenta_las_deducciones(): void
    {
        $componente = new GestionVentasGranos();
        $componente->cantidad_kg = '15000';
        $componente->factor = '100';
        $componente->precio_kg = '518.39';
        $componente->flete_tn = '1356.10';
        $componente->deducciones = '4296.19';
        $componente->ret_iva = '388795.42';
        $componente->iva_rg4310 = '427674.97';

        $this->assertEqualsWithDelta(8592320.39, $componente->totalCalculado(), 0.001);
        $this->assertEqualsWithDelta(8588024.20, $componente->importeListadoCalculado(), 0.001);
    }

    public function test_el_modelo_respeta_precios_historicos_anteriores_al_flete(): void
    {
        $venta = new VentaGrano([
            'cantidad_kg' => 15000,
            'factor' => 100,
            'precio_kg' => 519.75,
            'flete_kg' => 1.3561,
            'precio_kg_es_neto' => false,
        ]);

        $this->assertEqualsWithDelta(7775908.50, $venta->subtotal, 0.001);
    }

    public function test_el_modelo_no_resta_el_flete_a_un_precio_neto_nuevo(): void
    {
        $venta = new VentaGrano([
            'cantidad_kg' => 15000,
            'factor' => 100,
            'precio_kg' => 518.39,
            'flete_kg' => 1.3561,
            'precio_kg_es_neto' => true,
        ]);

        $this->assertEqualsWithDelta(7775850.00, $venta->subtotal, 0.001);
    }

    public function test_el_listado_descuenta_deducciones_en_ventas_nuevas(): void
    {
        $venta = new VentaGrano([
            'importe_total' => 8592320.39,
            'deducciones' => 4296.19,
            'precio_kg_es_neto' => true,
        ]);

        $this->assertEqualsWithDelta(8588024.20, $venta->importe_listado, 0.001);
    }

    public function test_el_listado_no_vuelve_a_descontar_deducciones_historicas(): void
    {
        $venta = new VentaGrano([
            'importe_total' => 8588024.20,
            'deducciones' => 4296.19,
            'precio_kg_es_neto' => false,
        ]);

        $this->assertEqualsWithDelta(8588024.20, $venta->importe_listado, 0.001);
    }

    public function test_la_cantidad_puede_ingresarse_y_reexpresarse_en_toneladas(): void
    {
        $componente = new GestionVentasGranos();
        $componente->unidadCantidad = 'tn';
        $componente->cantidadIngresada = '12.6';

        $componente->updatedCantidadIngresada();

        $this->assertSame('12600', $componente->cantidad_kg);

        $componente->unidadCantidad = 'quintales';
        $componente->updatedUnidadCantidad();
        $this->assertSame('126', $componente->cantidadIngresada);

        $componente->unidadCantidad = 'tn';
        $componente->updatedUnidadCantidad();
        $this->assertSame('12.6', $componente->cantidadIngresada);
    }

    public function test_la_unidad_elegida_es_un_campo_persistible_de_la_venta(): void
    {
        $venta = new VentaGrano(['unidad_cantidad' => 'tn']);

        $this->assertSame('tn', $venta->unidad_cantidad);
    }
}
