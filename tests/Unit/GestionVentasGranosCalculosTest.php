<?php

namespace Tests\Unit;

use App\Livewire\Ventas\GestionVentasGranos;
use App\Models\VentaGrano;
use Tests\TestCase;

class GestionVentasGranosCalculosTest extends TestCase
{
    public function test_el_flete_se_ingresa_por_tonelada_y_se_convierte_para_el_subtotal(): void
    {
        $componente = new GestionVentasGranos();
        $componente->cantidad_kg = '12600';
        $componente->factor = '100';
        $componente->precio_kg = '499.95';
        $componente->flete_tn = '3390.90';

        $this->assertEqualsWithDelta(6256644.66, $componente->subtotalCalculado(), 0.001);
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
