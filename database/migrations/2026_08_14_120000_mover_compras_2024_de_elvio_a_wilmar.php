<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * El export de Mis Comprobantes de WILMAR 2024 se importó con ELVIO
 * seleccionada en el selector de empresa, así que sus comprobantes quedaron
 * cargados en ELVIO.
 *
 * Se los devuelve a WILMAR. Se identifican por tres condiciones juntas:
 * están en ELVIO, tienen fecha de 2024, y se crearon el día de la importación.
 * Los comprobantes propios de ELVIO de 2024 ya estaban en la base desde antes,
 * así que el importador los detectó como duplicados y no creó ninguno: todo lo
 * que se creó ese día con fecha 2024 es de WILMAR.
 *
 * Al mover la compra hay que reapuntar el proveedor, porque los proveedores
 * también son por empresa: se busca uno con el mismo CUIT en WILMAR y, si no
 * existe, se crea copiando los datos del de ELVIO.
 *
 * Idempotente: si ya se corrigió, no encuentra nada y no hace nada.
 */
return new class extends Migration
{
    private const CUIT_ELVIO = '20-13543013-8';

    private const CUIT_WILMAR = '20-17520408-4';

    /** Día en que se hizo la importación equivocada. */
    private const DESDE = '2026-08-14 00:00:00';

    /** Si aparecen muchas más de las esperadas, algo no es lo que creemos. */
    private const TOPE = 300;

    public function up(): void
    {
        $elvio = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        $wilmar = DB::table('empresas')->where('cuit', self::CUIT_WILMAR)->value('id');

        if (! $elvio || ! $wilmar) {
            return;
        }

        $compras = DB::table('compras')
            ->where('id_empresa', $elvio)
            ->whereBetween('fecha', ['2024-01-01', '2024-12-31'])
            ->where('created_at', '>=', self::DESDE)
            ->where('observaciones', 'like', 'Importado desde ARCA%')
            ->get(['id', 'id_proveedor']);

        if ($compras->isEmpty()) {
            info('Compras 2024 mal ubicadas: no se encontró ninguna, no hay nada que mover.');

            return;
        }

        if ($compras->count() > self::TOPE) {
            info(sprintf('Compras 2024 mal ubicadas: aparecieron %d, más de las %d esperadas. No se movió nada.',
                $compras->count(), self::TOPE));

            return;
        }

        // Proveedor de ELVIO => proveedor equivalente en WILMAR
        $equivalencias = [];
        foreach ($compras->pluck('id_proveedor')->filter()->unique() as $idProveedorElvio) {
            $origen = DB::table('proveedores')->where('id', $idProveedorElvio)->first();
            if (! $origen) {
                continue;
            }

            $destino = DB::table('proveedores')
                ->where('id_empresa', $wilmar)
                ->where('cuit', $origen->cuit)
                ->value('id');

            if (! $destino) {
                $destino = (string) Str::uuid();
                DB::table('proveedores')->insert([
                    'id' => $destino,
                    'id_empresa' => $wilmar,
                    'nombre' => $origen->nombre,
                    'razon_social' => $origen->razon_social,
                    'cuit' => $origen->cuit,
                    'rubro' => $origen->rubro ?: 'otro',
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $equivalencias[$idProveedorElvio] = $destino;
        }

        foreach ($compras as $compra) {
            DB::table('compras')->where('id', $compra->id)->update([
                'id_empresa' => $wilmar,
                'id_proveedor' => $equivalencias[$compra->id_proveedor] ?? $compra->id_proveedor,
                'updated_at' => now(),
            ]);
        }

        info(sprintf('Compras 2024 mal ubicadas: se movieron %d de ELVIO a WILMAR.', $compras->count()));
    }

    public function down(): void
    {
        // No se revierte: volver a ELVIO sería reponer el error.
    }
};
