<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Rubro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SincronizarRubroCuit
{
    public static function asegurarCatalogo(string $codigo, string $origen, string $destino): void
    {
        if (isset(Rubro::catalogo($destino)[$codigo])) {
            return;
        }
        $datos = Rubro::catalogo($origen)[$codigo] ?? null;
        if ($datos) {
            Rubro::withoutGlobalScope('empresa')->withTrashed()->updateOrCreate(
                ['id_empresa' => $destino, 'codigo' => $codigo], $datos + ['deleted_at' => null],
            );
        }
    }

    public function ejecutar(Proveedor $origen): int
    {
        Gate::authorize('compras.proveedores.gestionar');
        $cuit = Proveedor::normalizarCuit($origen->cuit ?? '');
        if (! $cuit || ! $origen->rubro) {
            return 0;
        }

        return DB::transaction(function () use ($origen, $cuit) {
            $cantidad = 0;
            // Excepción al aislamiento limitada al mismo CUIT, autorizada desde su ficha.
            $proveedores = Proveedor::withoutGlobalScope('empresa')->porCuit($cuit)->get();
            foreach ($proveedores as $proveedor) {
                self::asegurarCatalogo($origen->rubro, $origen->id_empresa, $proveedor->id_empresa);
                if ($proveedor->id !== $origen->id) {
                    Proveedor::withoutGlobalScope('empresa')->whereKey($proveedor->id)->where('id_empresa', $proveedor->id_empresa)
                        ->update(['rubro' => $origen->rubro]);
                }
                if (Gate::allows('compras.editar')) {
                    $cantidad += Compra::withoutGlobalScope('empresa')->where('id_empresa', $proveedor->id_empresa)
                        ->where('id_proveedor', $proveedor->id)
                        ->where(fn ($q) => $q->whereNull('rubro')->orWhere('rubro', '!=', $origen->rubro))
                        ->update(['rubro' => $origen->rubro]);
                }
            }

            return $cantidad;
        });
    }
}
