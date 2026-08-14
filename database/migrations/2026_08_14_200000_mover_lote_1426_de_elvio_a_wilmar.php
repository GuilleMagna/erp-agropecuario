<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tercer y último intento de devolver a WILMAR sus comprobantes de 2024.
 *
 * Los dos anteriores fallaron por una suposición equivocada: creí que ELVIO ya
 * tenía sus comprobantes de 2024 cargados de antes. No era así. Consultando la
 * base quedó claro que ese día se importaron los dos archivos, uno detrás del
 * otro:
 *
 *   14:26  165 comprobantes  -> son de WILMAR, entraron con ELVIO seleccionada
 *   14:27  370 comprobantes  -> son de ELVIO, correctos
 *
 * La primera migración filtraba por día y encontraba los 535 juntos, así que
 * abortaba por pasarse del tope. La segunda buscaba el grupo más grande y daba
 * con el de 370. Ninguna miraba el de las 14:26.
 *
 * Acá se apunta directo a ese lote, con la cantidad como control: si no
 * encuentra entre 150 y 180 comprobantes, no toca nada.
 */
return new class extends Migration
{
    private const CUIT_ELVIO = '20-13543013-8';

    private const CUIT_WILMAR = '20-17520408-4';

    private const DESDE = '2026-08-14 14:26:00';

    private const HASTA = '2026-08-14 14:26:59';

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
            ->whereBetween('created_at', [self::DESDE, self::HASTA])
            ->get(['id', 'id_proveedor', 'observaciones']);

        if ($compras->count() < 150 || $compras->count() > 180) {
            info(sprintf('Lote 14:26: se esperaban ~165 comprobantes y hay %d. No se movió nada.', $compras->count()));

            return;
        }

        // Los proveedores son por empresa: hay que reapuntarlos a WILMAR.
        $equivalencias = [];
        foreach ($compras->pluck('id_proveedor')->filter()->unique() as $idOrigen) {
            $origen = DB::table('proveedores')->where('id', $idOrigen)->first();
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

            $equivalencias[$idOrigen] = $destino;
        }

        foreach ($compras as $compra) {
            DB::table('compras')->where('id', $compra->id)->update([
                'id_empresa' => $wilmar,
                'id_proveedor' => $equivalencias[$compra->id_proveedor] ?? $compra->id_proveedor,
                'observaciones' => trim(($compra->observaciones ?: '').' | Reasignado a WILMAR: se había importado en ELVIO por error.'),
                'updated_at' => now(),
            ]);
        }

        info(sprintf('Lote 14:26: se movieron %d comprobantes de ELVIO a WILMAR.', $compras->count()));
    }

    public function down(): void
    {
        // No se revierte: volver a ELVIO sería reponer el error.
    }
};
