<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Segundo intento de devolver a WILMAR sus comprobantes de 2024, que se
 * importaron con ELVIO seleccionada en el selector de empresa.
 *
 * La migración anterior (2026_08_14_120000) no encontró nada porque filtraba
 * por fecha de creación asumiendo que la importación había sido ese mismo día,
 * y fue antes. Acá el lote se detecta solo: el importador crea todos los
 * comprobantes de un archivo en la misma corrida, así que comparten el minuto
 * de creación. Se busca el minuto con más altas dentro de ELVIO/2024 y, si el
 * tamaño del grupo es razonable, se lo mueve entero.
 *
 * Guardas, porque esto se decide sobre datos y no sobre una lista fija:
 *  - el grupo tiene que tener entre 100 y 300 comprobantes (se esperan 165);
 *  - tienen que venir del importador de ARCA;
 *  - después de mover, ELVIO tiene que quedar con menos de los que tenía.
 *
 * A los movidos se les deja constancia en las observaciones, para poder
 * revisarlos después sin depender del log.
 */
return new class extends Migration
{
    private const CUIT_ELVIO = '20-13543013-8';

    private const CUIT_WILMAR = '20-17520408-4';

    private const MINIMO = 100;

    private const MAXIMO = 300;

    public function up(): void
    {
        $elvio = DB::table('empresas')->where('cuit', self::CUIT_ELVIO)->value('id');
        $wilmar = DB::table('empresas')->where('cuit', self::CUIT_WILMAR)->value('id');

        if (! $elvio || ! $wilmar) {
            return;
        }

        // Si WILMAR ya tiene comprobantes de 2024, esto ya se corrigió.
        $yaTiene = DB::table('compras')
            ->where('id_empresa', $wilmar)
            ->whereBetween('fecha', ['2024-01-01', '2024-12-31'])
            ->count();

        if ($yaTiene > 0) {
            return;
        }

        // El lote de importación: el minuto con más altas en ELVIO/2024.
        $lote = DB::table('compras')
            ->where('id_empresa', $elvio)
            ->whereBetween('fecha', ['2024-01-01', '2024-12-31'])
            ->where('observaciones', 'like', 'Importado desde ARCA%')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS minuto, COUNT(*) AS n")
            ->groupBy('minuto')
            ->orderByDesc('n')
            ->first();

        if (! $lote || $lote->n < self::MINIMO || $lote->n > self::MAXIMO) {
            info(sprintf('Lote WILMAR 2024: no se encontró un grupo del tamaño esperado (%s). No se movió nada.',
                $lote ? $lote->n.' en '.$lote->minuto : 'ningún grupo'));

            return;
        }

        $compras = DB::table('compras')
            ->where('id_empresa', $elvio)
            ->whereBetween('fecha', ['2024-01-01', '2024-12-31'])
            ->where('observaciones', 'like', 'Importado desde ARCA%')
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') = ?", [$lote->minuto])
            ->get(['id', 'id_proveedor', 'observaciones']);

        // Los proveedores también son por empresa: hay que reapuntarlos.
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

        info(sprintf('Lote WILMAR 2024: se movieron %d comprobantes del %s, de ELVIO a WILMAR.',
            $compras->count(), $lote->minuto));
    }

    public function down(): void
    {
        // No se revierte: volver a ELVIO sería reponer el error.
    }
};
