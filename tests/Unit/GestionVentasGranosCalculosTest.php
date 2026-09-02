<?php

namespace Tests\Unit;

use App\Livewire\Ventas\GestionVentasGranos;
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
}
