<?php

namespace App\Console\Commands;

use App\Models\Compra;
use Illuminate\Console\Command;

class CorregirComprasDuplicadas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'corregir:compras-duplicadas {--dry-run : No borra nada, solo muestra el resultado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Borra compras duplicadas (mismo numero_comprobante + proveedor + empresa) generadas por el bug de scope de empresa en MrbotService, conservando siempre la copia más antigua de cada par';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $grupos = Compra::sinFiltroDeEmpresa()
            ->selectRaw('numero_comprobante, id_empresa, id_proveedor')
            ->whereNotNull('numero_comprobante')
            ->groupBy('numero_comprobante', 'id_empresa', 'id_proveedor')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($grupos->isEmpty()) {
            $this->info('No se encontraron compras duplicadas.');
            return self::SUCCESS;
        }

        $borradas = 0;
        $omitidas = 0;
        $totalBorrado = 0.0;

        foreach ($grupos as $grupo) {
            $filas = Compra::sinFiltroDeEmpresa()
                ->with('proveedor')
                ->where('numero_comprobante', $grupo->numero_comprobante)
                ->where('id_empresa', $grupo->id_empresa)
                ->where('id_proveedor', $grupo->id_proveedor)
                ->orderBy('created_at')
                ->get();

            // Solo se tocan grupos donde todas las filas tienen el mismo importe,
            // ninguna tiene stock ya registrado y ninguna tiene imputación
            // (actividad/lote/campaña) — así nos aseguramos de no borrar una fila
            // que el usuario ya revisó o completó a mano.
            $totales = $filas->pluck('total')->unique();
            $algunaImputada = $filas->contains(fn ($f) => $f->actividad !== 'general' || $f->id_lote || $f->id_campana);
            $algunaConStock = $filas->contains('stock_registrado', true);

            if ($totales->count() > 1 || $algunaImputada || $algunaConStock) {
                $this->warn("Omitido {$grupo->numero_comprobante} (proveedor {$filas->first()->proveedor?->nombre}): difieren en importe o ya tienen imputación/stock, revisar a mano.");
                $omitidas += $filas->count() - 1;
                continue;
            }

            $original = $filas->first();
            $repetidas = $filas->slice(1);

            foreach ($repetidas as $repetida) {
                $this->line(sprintf(
                    'Borrar %s | %s | $%s | creada %s (se conserva la de %s)',
                    $grupo->numero_comprobante,
                    $original->proveedor?->nombre ?? '?',
                    number_format((float) $repetida->total, 2, ',', '.'),
                    $repetida->created_at,
                    $original->created_at
                ));

                if (!$dryRun) {
                    $repetida->delete();
                }

                $borradas++;
                $totalBorrado += (float) $repetida->total;
            }
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Compras duplicadas borradas: {$borradas}");
        $this->info('Total borrado: $' . number_format($totalBorrado, 2, ',', '.'));
        if ($omitidas > 0) {
            $this->warn("Omitidas (requieren revisión manual): {$omitidas}");
        }

        return self::SUCCESS;
    }
}
