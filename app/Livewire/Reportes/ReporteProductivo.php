<?php

namespace App\Livewire\Reportes;

use App\Models\Animal;
use App\Models\Campana;
use App\Models\Establecimiento;
use App\Models\Insumo;
use App\Models\Movimiento;
use App\Models\Siembra;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReporteProductivo extends Component
{
    public string $filtroEstablecimiento = '';
    public string $filtroCampana        = '';
    public string $filtroFechaDesde     = '';
    public string $filtroFechaHasta     = '';

    public function mount(): void
    {
        $this->filtroFechaDesde = now()->startOfYear()->format('Y-m-d');
        $this->filtroFechaHasta = now()->format('Y-m-d');
    }

    public function exportarCsv(): mixed
    {
        Gate::authorize('reportes.exportar');

        $desde = $this->filtroFechaDesde ?: now()->startOfYear()->format('Y-m-d');
        $hasta = $this->filtroFechaHasta ?: now()->format('Y-m-d');
        $estId = $this->filtroEstablecimiento ?: null;

        $stock   = $this->queryStockGanaderia($estId);
        $movs    = $this->queryMovimientos($estId, $desde, $hasta);
        $siembras = $this->querySiembras($estId);
        $cosechas = $this->queryCosechas($desde, $hasta);
        $insumos  = $this->queryInsumosStock();

        $filename = 'reporte-productivo-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($stock, $movs, $siembras, $cosechas, $insumos, $desde, $hasta) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($handle, ["REPORTE PRODUCTIVO — {$desde} al {$hasta}"], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, ['--- STOCK DE HACIENDA ---'], ';');
            fputcsv($handle, ['Categoría', 'Cabezas', 'Peso promedio (kg)'], ';');
            foreach ($stock as $row) {
                fputcsv($handle, [
                    Animal::CATEGORIAS[$row->categoria] ?? $row->categoria,
                    $row->total,
                    number_format((float)($row->peso_promedio ?? 0), 2, '.', ''),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['--- MOVIMIENTOS DEL PERÍODO ---'], ';');
            fputcsv($handle, ['Tipo', 'Operaciones', 'Cabezas'], ';');
            foreach ($movs as $row) {
                fputcsv($handle, [
                    Movimiento::TIPOS[$row->tipo] ?? $row->tipo,
                    $row->operaciones,
                    $row->total_cabezas,
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['--- AGRICULTURA — SIEMBRAS ---'], ';');
            fputcsv($handle, ['Cultivo', 'Lotes', 'Superficie (ha)'], ';');
            foreach ($siembras as $row) {
                fputcsv($handle, [
                    Siembra::CULTIVOS[$row->cultivo] ?? $row->cultivo,
                    $row->lotes,
                    number_format((float)($row->total_ha ?? 0), 2, '.', ''),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['--- COSECHAS DEL PERÍODO ---'], ';');
            fputcsv($handle, ['Cultivo', 'Producción (tn)', 'Superficie (ha)', 'Rinde (kg/ha)'], ';');
            foreach ($cosechas as $row) {
                fputcsv($handle, [
                    $row['cultivo_label'],
                    number_format($row['total_tn'], 3, '.', ''),
                    number_format($row['total_ha'], 2, '.', ''),
                    number_format($row['rinde_kg_ha'] ?? 0, 0, '.', ''),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['--- STOCK DE INSUMOS ---'], ';');
            fputcsv($handle, ['Insumo', 'Tipo', 'Unidad', 'Stock actual', 'Stock mínimo', 'Alerta'], ';');
            foreach ($insumos as $ins) {
                fputcsv($handle, [
                    $ins->nombre,
                    $ins->tipo_label,
                    $ins->unidad,
                    number_format($ins->stock_actual, 2, '.', ''),
                    number_format((float)($ins->stock_minimo ?? 0), 2, '.', ''),
                    ($ins->stock_minimo !== null && $ins->stock_actual < (float)$ins->stock_minimo) ? 'BAJO MÍNIMO' : '',
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function queryStockGanaderia(?string $estId)
    {
        return Animal::activos()
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->selectRaw('categoria, count(*) as total, avg(peso_actual_kg) as peso_promedio')
            ->groupBy('categoria')
            ->orderBy('total', 'desc')
            ->get();
    }

    private function queryMovimientos(?string $estId, string $desde, string $hasta)
    {
        return Movimiento::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->selectRaw('tipo, count(*) as operaciones, sum(cantidad) as total_cabezas')
            ->groupBy('tipo')
            ->get();
    }

    private function querySiembras(?string $estId)
    {
        return Siembra::query()
            ->when($this->filtroCampana, fn($q) => $q->where('id_campana', $this->filtroCampana))
            ->when($estId, fn($q) => $q->whereHas('lote', fn($q) => $q->where('id_establecimiento', $estId)))
            ->selectRaw('cultivo, count(*) as lotes, sum(superficie_sembrada_ha) as total_ha')
            ->groupBy('cultivo')
            ->orderBy('total_ha', 'desc')
            ->get();
    }

    private function queryCosechas(string $desde, string $hasta)
    {
        $campId = $this->filtroCampana ?: null;

        $siembras = Siembra::with(['cosechas' => fn($q) =>
                $q->whereBetween('fecha_cosecha', [$desde, $hasta])
            ])
            ->when($campId, fn($q) => $q->where('id_campana', $campId))
            ->get();

        return $siembras
            ->groupBy('cultivo')
            ->map(fn($grupo) => [
                'cultivo'       => $grupo->first()->cultivo,
                'cultivo_label' => $grupo->first()->cultivo_label,
                'total_kg'      => $grupo->flatMap->cosechas->sum('produccion_total_kg'),
                'total_tn'      => $grupo->flatMap->cosechas->sum('produccion_total_kg') / 1000,
                'total_ha'      => $grupo->flatMap->cosechas->sum('superficie_cosechada_ha'),
                'rinde_kg_ha'   => $grupo->flatMap->cosechas->avg('rinde_kg_ha'),
            ])
            ->filter(fn($c) => $c['total_kg'] > 0)
            ->sortByDesc('total_kg')
            ->values();
    }

    private function queryInsumosStock()
    {
        return Insumo::activos()
            ->withSum(['movimientosInsumos as total_entradas' => fn($q) =>
                $q->whereIn('tipo', ['entrada', 'ajuste_positivo'])
            ], 'cantidad')
            ->withSum(['movimientosInsumos as total_salidas' => fn($q) =>
                $q->whereNotIn('tipo', ['entrada', 'ajuste_positivo'])
            ], 'cantidad')
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get()
            ->map(function ($ins) {
                $ins->stock_actual = ((float)($ins->total_entradas ?? 0)) - ((float)($ins->total_salidas ?? 0));
                return $ins;
            });
    }

    public function render()
    {
        $desde = $this->filtroFechaDesde ?: now()->startOfYear()->format('Y-m-d');
        $hasta = $this->filtroFechaHasta ?: now()->format('Y-m-d');
        $estId = $this->filtroEstablecimiento ?: null;

        $stockPorCategoria  = $this->queryStockGanaderia($estId);
        $movimientosPeriodo = $this->queryMovimientos($estId, $desde, $hasta);
        $siembrasPorCultivo = $this->querySiembras($estId);
        $cosechasPorCultivo = $this->queryCosechas($desde, $hasta);
        $insumosStock       = $this->queryInsumosStock();

        $stockTotal     = $stockPorCategoria->sum('total');
        $insumosAlerta  = $insumosStock->filter(fn($i) => $i->stock_minimo !== null && $i->stock_actual < (float)$i->stock_minimo)->count();

        $establecimientos = Establecimiento::orderBy('nombre')->get();
        $campanas         = Campana::activos()->orderBy('nombre')->get();

        return view('livewire.reportes.reporte-productivo', compact(
            'stockPorCategoria', 'stockTotal',
            'movimientosPeriodo',
            'siembrasPorCultivo',
            'cosechasPorCultivo',
            'insumosStock', 'insumosAlerta',
            'establecimientos', 'campanas',
            'desde', 'hasta',
        ));
    }
}
