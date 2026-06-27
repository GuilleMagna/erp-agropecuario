<?php

namespace App\Livewire\Reportes;

use App\Models\Compra;
use App\Models\Cuenta;
use App\Models\Establecimiento;
use App\Models\Jornal;
use App\Models\Transaccion;
use App\Models\VentaGrano;
use App\Models\VentaHacienda;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReporteEconomico extends Component
{
    public string $filtroFechaDesde     = '';
    public string $filtroFechaHasta     = '';
    public string $filtroEstablecimiento = '';

    public function mount(): void
    {
        $this->filtroFechaDesde = now()->startOfYear()->format('Y-m-d');
        $this->filtroFechaHasta = now()->format('Y-m-d');
    }

    public function exportarCsv(): mixed
    {
        Gate::authorize('reportes.exportar');

        $desde = $this->filtroFechaDesde;
        $hasta = $this->filtroFechaHasta;
        $estId = $this->filtroEstablecimiento ?: null;

        $filename = 'reporte-economico-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($desde, $hasta, $estId) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ["REPORTE ECONÓMICO — {$desde} al {$hasta}"], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, ['--- VENTAS DE GRANOS ---'], ';');
            fputcsv($handle, ['Cereal', 'Operaciones', 'Cantidad (tn)', 'Importe'], ';');
            $granos = VentaGrano::whereBetween('fecha', [$desde, $hasta])
                ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
                ->whereNotIn('estado', ['cancelada'])
                ->selectRaw('cereal, count(*) as operaciones, sum(cantidad_tn) as total_tn, sum(importe_total) as total')
                ->groupBy('cereal')->get();
            foreach ($granos as $row) {
                fputcsv($handle, [
                    VentaGrano::CEREALES[$row->cereal] ?? $row->cereal,
                    $row->operaciones,
                    number_format((float)$row->total_tn, 3, '.', ''),
                    number_format((float)$row->total, 2, '.', ''),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['--- VENTAS DE HACIENDA ---'], ';');
            fputcsv($handle, ['Categoría', 'Operaciones', 'Cabezas', 'Importe'], ';');
            $hacienda = VentaHacienda::whereBetween('fecha', [$desde, $hasta])
                ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
                ->whereNotIn('estado', ['cancelada'])
                ->selectRaw('categoria, count(*) as operaciones, sum(cantidad_cabezas) as total_cabezas, sum(importe_total) as total')
                ->groupBy('categoria')->get();
            foreach ($hacienda as $row) {
                fputcsv($handle, [
                    VentaHacienda::CATEGORIAS[$row->categoria] ?? $row->categoria,
                    $row->operaciones,
                    $row->total_cabezas,
                    number_format((float)$row->total, 2, '.', ''),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['--- COMPRAS ---'], ';');
            fputcsv($handle, ['Estado', 'Compras', 'Total'], ';');
            $compras = Compra::whereBetween('fecha', [$desde, $hasta])
                ->selectRaw('estado, count(*) as cantidad, sum(total) as total_importe')
                ->groupBy('estado')->get();
            foreach ($compras as $row) {
                fputcsv($handle, [
                    Compra::ESTADOS[$row->estado] ?? $row->estado,
                    $row->cantidad,
                    number_format((float)$row->total_importe, 2, '.', ''),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['--- TRANSACCIONES ---'], ';');
            fputcsv($handle, ['Tipo', 'Categoría', 'Importe'], ';');
            $transacciones = Transaccion::whereBetween('fecha', [$desde, $hasta])
                ->selectRaw('tipo, categoria, sum(importe) as total')
                ->groupBy('tipo', 'categoria')
                ->orderBy('tipo')->orderBy('total', 'desc')->get();
            foreach ($transacciones as $row) {
                fputcsv($handle, [$row->tipo, $row->categoria, number_format((float)$row->total, 2, '.', '')], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $desde = $this->filtroFechaDesde ?: now()->startOfYear()->format('Y-m-d');
        $hasta = $this->filtroFechaHasta ?: now()->format('Y-m-d');
        $estId = $this->filtroEstablecimiento ?: null;

        // === VENTAS GRANOS ===
        $ventasGranosPorCereal = VentaGrano::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->whereNotIn('estado', ['cancelada'])
            ->selectRaw('cereal, count(*) as operaciones, sum(cantidad_tn) as total_tn, sum(importe_total) as total_importe')
            ->groupBy('cereal')
            ->orderBy('total_importe', 'desc')
            ->get();

        $totalVentasGranos = $ventasGranosPorCereal->sum('total_importe');

        // === VENTAS HACIENDA ===
        $ventasHaciendaPorCategoria = VentaHacienda::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->whereNotIn('estado', ['cancelada'])
            ->selectRaw('categoria, count(*) as operaciones, sum(cantidad_cabezas) as total_cabezas, sum(importe_total) as total_importe')
            ->groupBy('categoria')
            ->orderBy('total_importe', 'desc')
            ->get();

        $totalVentasHacienda = $ventasHaciendaPorCategoria->sum('total_importe');

        // === COMPRAS ===
        $comprasPorEstado = Compra::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->selectRaw('estado, count(*) as cantidad, sum(total) as total_importe, sum(iva_importe) as total_iva')
            ->groupBy('estado')
            ->get();

        $totalCompras = $comprasPorEstado->whereNotIn('estado', ['cancelada'])->sum('total_importe');

        // === TRANSACCIONES ===
        $totalIngresos = Transaccion::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->where('tipo', 'ingreso')->sum('importe');

        $totalEgresos = Transaccion::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->where('tipo', 'egreso')->sum('importe');

        $ingresosPorCategoria = Transaccion::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->where('tipo', 'ingreso')
            ->selectRaw('categoria, sum(importe) as total')
            ->groupBy('categoria')
            ->orderBy('total', 'desc')
            ->get();

        $egresosPorCategoria = Transaccion::whereBetween('fecha', [$desde, $hasta])
            ->when($estId, fn($q) => $q->where('id_establecimiento', $estId))
            ->where('tipo', 'egreso')
            ->selectRaw('categoria, sum(importe) as total')
            ->groupBy('categoria')
            ->orderBy('total', 'desc')
            ->get();

        // === CUENTAS ===
        $cuentas = Cuenta::activas()
            ->withSum(['transacciones as total_ingresos' => fn($q) => $q->where('tipo', 'ingreso')], 'importe')
            ->withSum(['transacciones as total_egresos' => fn($q) => $q->where('tipo', 'egreso')], 'importe')
            ->get()
            ->map(function ($c) {
                $c->saldo_actual = ((float)$c->saldo_inicial)
                    + ((float)($c->total_ingresos ?? 0))
                    - ((float)($c->total_egresos ?? 0));
                return $c;
            });

        $saldoCuentas = $cuentas->sum('saldo_actual');

        // === JORNALES ===
        $jornalesPendientes = Jornal::whereBetween('fecha', [$desde, $hasta])
            ->where('estado', 'pendiente')->sum('importe');
        $jornalesLiquidados = Jornal::whereBetween('fecha', [$desde, $hasta])
            ->where('estado', 'liquidado')->sum('importe');

        $establecimientos = Establecimiento::orderBy('nombre')->get();

        return view('livewire.reportes.reporte-economico', compact(
            'ventasGranosPorCereal', 'totalVentasGranos',
            'ventasHaciendaPorCategoria', 'totalVentasHacienda',
            'comprasPorEstado', 'totalCompras',
            'totalIngresos', 'totalEgresos',
            'ingresosPorCategoria', 'egresosPorCategoria',
            'cuentas', 'saldoCuentas',
            'jornalesPendientes', 'jornalesLiquidados',
            'establecimientos',
            'desde', 'hasta',
        ));
    }
}
