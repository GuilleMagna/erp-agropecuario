<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Proveedor;

class CompletarClasificacionCompras
{
    public function ejecutar(?Proveedor $proveedor = null): int
    {
        $cantidad = 0;
        Compra::with('proveedor')
            ->when($proveedor, fn ($q) => $q->where('id_proveedor', $proveedor->id)->where('id_empresa', $proveedor->id_empresa))
            ->where(function ($q) {
                $q->whereNull('actividad')->orWhere('actividad', '')
                    ->orWhereNull('rubro')->orWhereIn('rubro', ['', 'otro'])->orWhereNull('zona')->orWhere('zona', '');
            })->chunkById(200, function ($compras) use (&$cantidad) {
                foreach ($compras as $compra) {
                    if (! $compra->proveedor || $compra->proveedor->id_empresa !== $compra->id_empresa) {
                        continue;
                    }
                    $cambios = [];
                    $rubro = $compra->rubroSugerido();
                    if ((! $compra->rubro || $compra->rubro === 'otro') && $rubro && $rubro !== 'otro') {
                        $cambios['rubro'] = $rubro;
                    }
                    if (! $compra->actividad && ($actividad = $compra->actividadSugerida())) {
                        $cambios['actividad'] = $actividad;
                    }
                    if (! $compra->zona) {
                        $cambios['zona'] = $compra->proveedor->zonaPredeterminada();
                    }
                    if ($cambios) {
                        // No pisar una clasificación que cambió mientras se procesaba el lote.
                        $cantidad += Compra::whereKey($compra->id)->where('id_empresa', $compra->id_empresa)
                            ->where('id_proveedor', $compra->id_proveedor)
                            ->where('zona', $compra->zona)->where('actividad', $compra->actividad)->where('rubro', $compra->rubro)
                            ->update($cambios);
                    }
                }
            });

        return $cantidad;
    }
}
