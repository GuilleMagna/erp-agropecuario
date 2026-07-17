<?php

namespace App\Livewire\Reportes;

use App\Models\IngresoAlquiler;
use App\Models\Inmueble;
use App\Models\PagoGastoNoArca;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReporteInmuebles extends Component
{
    public int $filtroAnio;

    public function mount(): void
    {
        $this->filtroAnio = (int) now()->format('Y');
    }

    /**
     * Ingresos por alquiler vs. gastos NO ARCA tageados a cada inmueble,
     * mes a mes y acumulado en el año.
     */
    private function calcularDatos(int $anio): array
    {
        $inmuebles = Inmueble::orderBy('nombre')->get();

        $ingresosPorInmuebleMes = IngresoAlquiler::whereYear('mes', $anio)
            ->selectRaw('id_inmueble, MONTH(mes) as mes_num, SUM(importe) as total')
            ->groupBy('id_inmueble', 'mes_num')
            ->get()
            ->groupBy('id_inmueble');

        $gastosPorInmuebleMes = PagoGastoNoArca::whereYear('pagos_gasto_no_arca.mes', $anio)
            ->join('gastos_no_arca', 'gastos_no_arca.id', '=', 'pagos_gasto_no_arca.id_gasto')
            ->whereNotNull('gastos_no_arca.id_inmueble')
            ->selectRaw('gastos_no_arca.id_inmueble as id_inmueble, MONTH(pagos_gasto_no_arca.mes) as mes_num, SUM(pagos_gasto_no_arca.importe) as total')
            ->groupBy('gastos_no_arca.id_inmueble', 'mes_num')
            ->get()
            ->groupBy('id_inmueble');

        $meses = [];
        $totalesAnio = ['ingresos' => 0.0, 'gastos' => 0.0, 'resultado' => 0.0];
        $acumuladoPorInmueble = [];
        foreach ($inmuebles as $inmueble) {
            $acumuladoPorInmueble[$inmueble->id] = 0.0;
        }

        for ($mes = 1; $mes <= 12; $mes++) {
            $periodo = sprintf('%04d-%02d', $anio, $mes);

            $filaInmuebles = [];
            $totalMes = ['ingresos' => 0.0, 'gastos' => 0.0, 'resultado' => 0.0];

            foreach ($inmuebles as $inmueble) {
                $ingresos = (float) (($ingresosPorInmuebleMes[$inmueble->id] ?? collect())
                    ->firstWhere('mes_num', $mes)?->total ?? 0);
                $gastos = (float) (($gastosPorInmuebleMes[$inmueble->id] ?? collect())
                    ->firstWhere('mes_num', $mes)?->total ?? 0);

                $resultado = $ingresos - $gastos;
                $acumuladoPorInmueble[$inmueble->id] += $resultado;

                $filaInmuebles[$inmueble->id] = [
                    'ingresos'  => $ingresos,
                    'gastos'    => $gastos,
                    'resultado' => $resultado,
                    'acumulado' => $acumuladoPorInmueble[$inmueble->id],
                ];

                $totalMes['ingresos'] += $ingresos;
                $totalMes['gastos'] += $gastos;
            }

            $totalMes['resultado'] = $totalMes['ingresos'] - $totalMes['gastos'];

            $meses[$mes] = [
                'nombre'    => ucfirst(Carbon::createFromDate($anio, $mes, 1)->locale('es')->isoFormat('MMM')),
                'periodo'   => $periodo,
                'inmuebles' => $filaInmuebles,
                'total'     => $totalMes,
            ];

            $totalesAnio['ingresos'] += $totalMes['ingresos'];
            $totalesAnio['gastos'] += $totalMes['gastos'];
        }
        $totalesAnio['resultado'] = $totalesAnio['ingresos'] - $totalesAnio['gastos'];

        return [$inmuebles, $meses, $totalesAnio];
    }

    public function exportarCsv(): mixed
    {
        Gate::authorize('reportes.exportar');

        $anio = $this->filtroAnio;
        [$inmuebles, $meses, $totalesAnio] = $this->calcularDatos($anio);
        $filename = "reporte-inmuebles-{$anio}.csv";

        return response()->streamDownload(function () use ($inmuebles, $meses, $totalesAnio, $anio) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ["INGRESOS Y GASTOS POR INMUEBLE — {$anio}"], ';');
            fputcsv($handle, [], ';');

            $header = ['Mes'];
            foreach ($inmuebles as $inmueble) {
                $header[] = $inmueble->nombre . ' - Ingresos';
                $header[] = $inmueble->nombre . ' - Gastos';
                $header[] = $inmueble->nombre . ' - Resultado';
            }
            $header[] = 'TOTAL Ingresos';
            $header[] = 'TOTAL Gastos';
            $header[] = 'TOTAL Resultado';
            fputcsv($handle, $header, ';');

            foreach ($meses as $datosMes) {
                $row = [$datosMes['nombre']];
                foreach ($inmuebles as $inmueble) {
                    $i = $datosMes['inmuebles'][$inmueble->id];
                    $row[] = number_format($i['ingresos'], 2, '.', '');
                    $row[] = number_format($i['gastos'], 2, '.', '');
                    $row[] = number_format($i['resultado'], 2, '.', '');
                }
                $row[] = number_format($datosMes['total']['ingresos'], 2, '.', '');
                $row[] = number_format($datosMes['total']['gastos'], 2, '.', '');
                $row[] = number_format($datosMes['total']['resultado'], 2, '.', '');
                fputcsv($handle, $row, ';');
            }

            fputcsv($handle, [], ';');
            $filaTotal = array_merge(
                ['Total ' . $anio],
                array_fill(0, $inmuebles->count() * 3, ''),
                [
                    number_format($totalesAnio['ingresos'], 2, '.', ''),
                    number_format($totalesAnio['gastos'], 2, '.', ''),
                    number_format($totalesAnio['resultado'], 2, '.', ''),
                ]
            );
            fputcsv($handle, $filaTotal, ';');

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        [$inmuebles, $meses, $totalesAnio] = $this->calcularDatos($this->filtroAnio);

        return view('livewire.reportes.reporte-inmuebles', [
            'inmuebles'   => $inmuebles,
            'meses'       => $meses,
            'totalesAnio' => $totalesAnio,
            'anio'        => $this->filtroAnio,
        ]);
    }
}
