<?php

namespace App\Livewire\Reportes;

use App\Models\Compra;
use App\Models\Empresa;
use App\Models\PagoGastoNoArca;
use App\Models\VentaGrano;
use App\Models\VentaHacienda;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReporteGanancias extends Component
{
    public int $filtroAnio;

    public function mount(): void
    {
        $this->filtroAnio = (int) now()->format('Y');
    }

    /**
     * Resultado impositivo estimado (ingresos - egresos, neto de IVA) mes a mes y
     * acumulado en el año, por empresa. Es la base para Ganancias, sin aplicar
     * escalas ni deducciones personales (eso requiere datos que no están en el ERP).
     */
    private function calcularDatos(int $anio): array
    {
        $empresas = Empresa::orderBy('razon_social')->get();

        // gastos_no_arca hoy no está asignado a ninguna empresa puntual (todas las
        // filas tienen id_empresa nulo), así que solo se puede sumar al consolidado.
        $gastosNoArcaPorMes = PagoGastoNoArca::whereYear('mes', $anio)
            ->selectRaw('MONTH(mes) as mes_num, SUM(importe) as total')
            ->groupBy('mes_num')
            ->pluck('total', 'mes_num');

        $meses = [];
        $totalesAnio = ['ingresos' => 0.0, 'egresos' => 0.0, 'resultado' => 0.0];
        $acumuladoPorEmpresa = [];
        foreach ($empresas as $empresa) {
            $acumuladoPorEmpresa[$empresa->id] = 0.0;
        }
        $acumuladoTotal = 0.0;

        for ($mes = 1; $mes <= 12; $mes++) {
            $periodo = sprintf('%04d-%02d', $anio, $mes);

            $filaEmpresas = [];
            $totalMes = ['ingresos' => 0.0, 'egresos' => 0.0, 'resultado' => 0.0];

            foreach ($empresas as $empresa) {
                $ingresos = (float) VentaGrano::sinFiltroDeEmpresa()
                        ->where('id_empresa', $empresa->id)
                        ->where('fecha', 'like', $periodo . '%')
                        ->whereNotIn('estado', ['cancelada', 'borrador'])
                        ->sum('importe_total')
                    + (float) VentaHacienda::sinFiltroDeEmpresa()
                        ->where('id_empresa', $empresa->id)
                        ->where('fecha', 'like', $periodo . '%')
                        ->where('estado', '!=', 'cancelada')
                        ->sum('importe_total');

                $egresos = (float) Compra::sinFiltroDeEmpresa()
                    ->where('id_empresa', $empresa->id)
                    ->where('fecha', 'like', $periodo . '%')
                    ->where('estado', '!=', 'cancelada')
                    ->sum('subtotal');

                $resultado = $ingresos - $egresos;
                $acumuladoPorEmpresa[$empresa->id] += $resultado;

                $filaEmpresas[$empresa->id] = [
                    'ingresos'  => $ingresos,
                    'egresos'   => $egresos,
                    'resultado' => $resultado,
                    'acumulado' => $acumuladoPorEmpresa[$empresa->id],
                ];

                $totalMes['ingresos'] += $ingresos;
                $totalMes['egresos'] += $egresos;
            }

            // Gastos NO ARCA: solo se suman al consolidado (ver nota arriba).
            $gastosNoArcaMes = (float) ($gastosNoArcaPorMes[$mes] ?? 0);
            $totalMes['egresos'] += $gastosNoArcaMes;
            $totalMes['resultado'] = $totalMes['ingresos'] - $totalMes['egresos'];
            $acumuladoTotal += $totalMes['resultado'];
            $totalMes['acumulado'] = $acumuladoTotal;
            $totalMes['gastos_no_arca'] = $gastosNoArcaMes;

            $meses[$mes] = [
                'nombre'   => ucfirst(Carbon::createFromDate($anio, $mes, 1)->locale('es')->isoFormat('MMM')),
                'desde'    => Carbon::createFromDate($anio, $mes, 1)->format('Y-m-d'),
                'hasta'    => Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->format('Y-m-d'),
                'periodo'  => $periodo,
                'empresas' => $filaEmpresas,
                'total'    => $totalMes,
            ];

            $totalesAnio['ingresos'] += $totalMes['ingresos'];
            $totalesAnio['egresos'] += $totalMes['egresos'];
        }
        $totalesAnio['resultado'] = $totalesAnio['ingresos'] - $totalesAnio['egresos'];

        return [$empresas, $meses, $totalesAnio];
    }

    public function exportarCsv(): mixed
    {
        Gate::authorize('reportes.exportar');

        $anio = $this->filtroAnio;
        [$empresas, $meses, $totalesAnio] = $this->calcularDatos($anio);
        $filename = "reporte-ganancias-{$anio}.csv";

        return response()->streamDownload(function () use ($empresas, $meses, $totalesAnio, $anio) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ["RESULTADO IMPOSITIVO ESTIMADO — {$anio}"], ';');
            fputcsv($handle, ['(ingresos - egresos, neto de IVA; base para Ganancias, sin escalas ni deducciones personales)'], ';');
            fputcsv($handle, [], ';');

            $header = ['Mes'];
            foreach ($empresas as $empresa) {
                $header[] = $empresa->razon_social . ' - Ingresos';
                $header[] = $empresa->razon_social . ' - Egresos';
                $header[] = $empresa->razon_social . ' - Resultado';
            }
            $header[] = 'TOTAL Ingresos';
            $header[] = 'TOTAL Egresos (incl. NO ARCA)';
            $header[] = 'TOTAL Resultado';
            $header[] = 'Acumulado año';
            fputcsv($handle, $header, ';');

            foreach ($meses as $datosMes) {
                $row = [$datosMes['nombre']];
                foreach ($empresas as $empresa) {
                    $e = $datosMes['empresas'][$empresa->id];
                    $row[] = number_format($e['ingresos'], 2, '.', '');
                    $row[] = number_format($e['egresos'], 2, '.', '');
                    $row[] = number_format($e['resultado'], 2, '.', '');
                }
                $row[] = number_format($datosMes['total']['ingresos'], 2, '.', '');
                $row[] = number_format($datosMes['total']['egresos'], 2, '.', '');
                $row[] = number_format($datosMes['total']['resultado'], 2, '.', '');
                $row[] = number_format($datosMes['total']['acumulado'], 2, '.', '');
                fputcsv($handle, $row, ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, [
                'Total ' . $anio, '', '', '', '', '', '', '', '',
                number_format($totalesAnio['ingresos'], 2, '.', ''),
                number_format($totalesAnio['egresos'], 2, '.', ''),
                number_format($totalesAnio['resultado'], 2, '.', ''),
            ], ';');

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        [$empresas, $meses, $totalesAnio] = $this->calcularDatos($this->filtroAnio);

        return view('livewire.reportes.reporte-ganancias', [
            'empresas'    => $empresas,
            'meses'       => $meses,
            'totalesAnio' => $totalesAnio,
            'anio'        => $this->filtroAnio,
        ]);
    }
}
