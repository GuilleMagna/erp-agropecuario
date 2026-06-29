<?php

namespace App\Livewire\Reportes;

use App\Models\Compra;
use App\Models\Establecimiento;
use App\Models\VentaGrano;
use App\Models\VentaHacienda;
use App\Models\Campana;
use App\Models\Lote;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReporteFiscal extends Component
{
    public string $filtroFechaDesde      = '';
    public string $filtroFechaHasta      = '';
    public string $filtroEstablecimiento = '';

    public function mount(): void
    {
        $this->filtroFechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroFechaHasta = now()->format('Y-m-d');
    }

    public function exportarCsv(): mixed
    {
        Gate::authorize('reportes.exportar');

        $desde    = $this->filtroFechaDesde;
        $hasta    = $this->filtroFechaHasta;
        $filename = 'reporte-fiscal-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($desde, $hasta) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ["REPORTE FISCAL — {$desde} al {$hasta}"], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, ['--- COMPRAS (IVA CRÉDITO) ---'], ';');
            fputcsv($handle, ['Tipo comprobante', 'Cantidad', 'Subtotal', 'IVA', 'Total'], ';');
            $compras = Compra::whereBetween('fecha', [$desde, $hasta])
                ->whereNotIn('estado', ['cancelada'])
                ->selectRaw('tipo_comprobante, count(*) as cantidad, sum(subtotal) as sum_subtotal, sum(iva_importe) as sum_iva, sum(total) as sum_total')
                ->groupBy('tipo_comprobante')->get();
            foreach ($compras as $row) {
                fputcsv($handle, [
                    Compra::TIPOS_COMPROBANTE[$row->tipo_comprobante] ?? $row->tipo_comprobante,
                    $row->cantidad,
                    number_format((float)$row->sum_subtotal, 2, '.', ''),
                    number_format((float)$row->sum_iva, 2, '.', ''),
                    number_format((float)$row->sum_total, 2, '.', ''),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['--- IMPUTACIÓN POR ACTIVIDAD ---'], ';');
            fputcsv($handle, ['Actividad', 'Comprobantes', 'Neto', 'IVA', 'Total'], ';');
            $impActividad = Compra::whereBetween('fecha', [$desde, $hasta])
                ->whereNotIn('estado', ['cancelada'])
                ->selectRaw('COALESCE(actividad, "sin_imputar") as actividad, count(*) as cantidad, sum(subtotal) as sum_subtotal, sum(iva_importe) as sum_iva, sum(total) as sum_total')
                ->groupBy('actividad')->orderByDesc('sum_total')->get();
            foreach ($impActividad as $row) {
                $label = Compra::ACTIVIDADES[$row->actividad] ?? 'Sin imputar';
                fputcsv($handle, [$label, $row->cantidad, number_format((float)$row->sum_subtotal, 2, '.', ''), number_format((float)$row->sum_iva, 2, '.', ''), number_format((float)$row->sum_total, 2, '.', '')], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['--- VENTAS (IVA DÉBITO) ---'], ';');
            fputcsv($handle, ['Concepto', 'Cantidad', 'Importe'], ';');
            $totalGranos = VentaGrano::whereBetween('fecha', [$desde, $hasta])->whereNotIn('estado', ['cancelada', 'borrador'])->sum('importe_total');
            $totalHacienda = VentaHacienda::whereBetween('fecha', [$desde, $hasta])->whereNotIn('estado', ['cancelada'])->sum('importe_total');
            fputcsv($handle, ['Ventas de granos', VentaGrano::whereBetween('fecha', [$desde, $hasta])->whereNotIn('estado', ['cancelada', 'borrador'])->count(), number_format((float)$totalGranos, 2, '.', '')], ';');
            fputcsv($handle, ['Ventas de hacienda', VentaHacienda::whereBetween('fecha', [$desde, $hasta])->whereNotIn('estado', ['cancelada'])->count(), number_format((float)$totalHacienda, 2, '.', '')], ';');

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $desde = $this->filtroFechaDesde ?: now()->startOfMonth()->format('Y-m-d');
        $hasta = $this->filtroFechaHasta ?: now()->format('Y-m-d');
        $estId = $this->filtroEstablecimiento ?: null;

        // === COMPRAS — IVA CRÉDITO ===
        $comprasPorTipo = Compra::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->whereNotIn('estado', ['cancelada'])
            ->selectRaw('tipo_comprobante, count(*) as cantidad, sum(subtotal) as sum_subtotal, sum(iva_importe) as sum_iva, sum(total) as sum_total')
            ->groupBy('tipo_comprobante')
            ->orderBy('sum_total', 'desc')
            ->get();

        $totalIvaCredito   = $comprasPorTipo->sum('sum_iva');
        $totalComprasBruto = $comprasPorTipo->sum('sum_total');
        $totalComprasNeto  = $comprasPorTipo->sum('sum_subtotal');

        // Detalle de compras
        $detalleCompras = Compra::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->whereNotIn('estado', ['cancelada'])
            ->with('proveedor')
            ->orderBy('fecha', 'desc')
            ->get();

        // === VENTAS — IVA DÉBITO (estimado) ===
        $ventasGranosPorCereal = VentaGrano::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->whereNotIn('estado', ['cancelada', 'borrador'])
            ->selectRaw('cereal, moneda, count(*) as operaciones, sum(cantidad_tn) as total_tn, sum(importe_total) as total_importe')
            ->groupBy('cereal', 'moneda')
            ->orderBy('total_importe', 'desc')
            ->get();

        $ventasHaciendaPorCategoria = VentaHacienda::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->whereNotIn('estado', ['cancelada'])
            ->selectRaw('categoria, moneda, count(*) as operaciones, sum(cantidad_cabezas) as total_cabezas, sum(importe_total) as total_importe')
            ->groupBy('categoria', 'moneda')
            ->orderBy('total_importe', 'desc')
            ->get();

        $totalVentasGranosArs     = $ventasGranosPorCereal->where('moneda', 'ARS')->sum('total_importe');
        $totalVentasHaciendaArs   = $ventasHaciendaPorCategoria->where('moneda', 'ARS')->sum('total_importe');
        $totalVentasGranosUsd     = $ventasGranosPorCereal->where('moneda', 'USD')->sum('total_importe');
        $totalVentasHaciendaUsd   = $ventasHaciendaPorCategoria->where('moneda', 'USD')->sum('total_importe');

        // === IMPUTACIÓN POR ACTIVIDAD ===
        $comprasPorActividad = Compra::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->whereNotIn('estado', ['cancelada'])
            ->selectRaw('COALESCE(actividad, "sin_imputar") as actividad, count(*) as cantidad, sum(subtotal) as sum_subtotal, sum(iva_importe) as sum_iva, sum(total) as sum_total')
            ->groupBy('actividad')
            ->orderByDesc('sum_total')
            ->get();

        // === IMPUTACIÓN POR LOTE ===
        $comprasPorLote = Compra::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->whereNotIn('estado', ['cancelada'])
            ->whereNotNull('id_lote')
            ->with('lote')
            ->selectRaw('id_lote, count(*) as cantidad, sum(subtotal) as sum_subtotal, sum(iva_importe) as sum_iva, sum(total) as sum_total')
            ->groupBy('id_lote')
            ->orderByDesc('sum_total')
            ->get();

        // === IMPUTACIÓN POR CAMPAÑA ===
        $comprasPorCampana = Compra::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->whereNotIn('estado', ['cancelada'])
            ->whereNotNull('id_campana')
            ->with('campana')
            ->selectRaw('id_campana, count(*) as cantidad, sum(subtotal) as sum_subtotal, sum(iva_importe) as sum_iva, sum(total) as sum_total')
            ->groupBy('id_campana')
            ->orderByDesc('sum_total')
            ->get();

        $totalSinImputar = $comprasPorActividad->where('actividad', 'sin_imputar')->sum('sum_total');

        $establecimientos = Establecimiento::orderBy('nombre')->get();

        return view('livewire.reportes.reporte-fiscal', compact(
            'comprasPorTipo', 'totalIvaCredito', 'totalComprasBruto', 'totalComprasNeto',
            'detalleCompras',
            'ventasGranosPorCereal', 'ventasHaciendaPorCategoria',
            'totalVentasGranosArs', 'totalVentasHaciendaArs',
            'totalVentasGranosUsd', 'totalVentasHaciendaUsd',
            'comprasPorActividad', 'comprasPorLote', 'comprasPorCampana',
            'totalSinImputar',
            'establecimientos',
            'desde', 'hasta',
        ));
    }
}
