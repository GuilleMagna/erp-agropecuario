<?php

namespace App\Livewire\Reportes;

use App\Models\Empresa;
use App\Models\PeriodoFiscal;
use App\Models\ReintegroIva;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReporteIva extends Component
{
    public int $filtroAnio;
    public string $agrupacion = 'empresa';

    public function mount(): void
    {
        $this->filtroAnio = (int) now()->format('Y');
    }

    /**
     * Arma la tabla mes a mes (débito, crédito, retenciones, devolución y saldo) por empresa,
     * replicando la lógica de la hoja "SUMA POR MES" de la planilla de control.
     */
    private function calcularDatos(int $anio): array
    {
        $empresas = Empresa::orderBy('razon_social')->get();

        $meses = [];
        $totalesAnio = ['credito' => 0.0, 'debito' => 0.0, 'retenido' => 0.0, 'devolucion' => 0.0, 'saldo' => 0.0];

        for ($mes = 1; $mes <= 12; $mes++) {
            $periodo = sprintf('%04d-%02d', $anio, $mes);
            $periodoFiscal = new PeriodoFiscal(['periodo' => $periodo]);

            $filaEmpresas = [];
            $totalMes = ['credito' => 0.0, 'debito' => 0.0, 'retenido' => 0.0, 'devolucion' => 0.0, 'saldo' => 0.0];

            foreach ($empresas as $empresa) {
                $credito = $periodoFiscal->ivaCredito($empresa->id);
                $debito = $periodoFiscal->ivaDebito($empresa->id);
                $retenido = $periodoFiscal->ivaRetenido($empresa->id);
                $devolucion = (float) ReintegroIva::sinFiltroDeEmpresa()
                    ->where('id_empresa', $empresa->id)
                    ->where('periodo', $periodo)
                    ->where('estado', 'acreditado')
                    ->sum('importe');
                $saldo = $debito - $credito - $retenido + $devolucion;

                $filaEmpresas[$empresa->id] = compact('credito', 'debito', 'retenido', 'devolucion', 'saldo');

                $totalMes['credito'] += $credito;
                $totalMes['debito'] += $debito;
                $totalMes['retenido'] += $retenido;
                $totalMes['devolucion'] += $devolucion;
                $totalMes['saldo'] += $saldo;
            }

            $meses[$mes] = [
                'nombre' => ucfirst(Carbon::createFromDate($anio, $mes, 1)->locale('es')->isoFormat('MMM')),
                'desde' => Carbon::createFromDate($anio, $mes, 1)->format('Y-m-d'),
                'hasta' => Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->format('Y-m-d'),
                'periodo' => $periodo,
                'empresas' => $filaEmpresas,
                'total' => $totalMes,
            ];

            foreach (['credito', 'debito', 'retenido', 'devolucion', 'saldo'] as $clave) {
                $totalesAnio[$clave] += $totalMes[$clave];
            }
        }

        return [$empresas, $meses, $totalesAnio];
    }

    public function exportarCsv(): mixed
    {
        Gate::authorize('reportes.exportar');

        $anio = $this->filtroAnio;
        [$empresas, $meses, $totalesAnio] = $this->calcularDatos($anio);
        $filename = "reporte-iva-{$anio}.csv";

        $secciones = [
            'credito'    => 'IVA CRÉDITO (compras, real)',
            'debito'     => 'IVA DÉBITO (ventas, estimado al 10,5%)',
            'retenido'   => 'IVA RETENIDO (ventas de granos)',
            'devolucion' => 'DEVOLUCIÓN IVA (reintegros acreditados)',
            'saldo'      => 'SALDO IVA (débito - crédito - retenciones + devolución)',
        ];

        return response()->streamDownload(function () use ($empresas, $meses, $totalesAnio, $anio, $secciones) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ["REPORTE IVA MENSUAL — {$anio}"], ';');
            fputcsv($handle, [], ';');

            foreach ($secciones as $clave => $titulo) {
                fputcsv($handle, ["--- {$titulo} ---"], ';');

                $header = ['Mes'];
                foreach ($empresas as $empresa) {
                    $header[] = $empresa->razon_social;
                }
                $header[] = 'TOTAL';
                fputcsv($handle, $header, ';');

                foreach ($meses as $datosMes) {
                    $row = [$datosMes['nombre']];
                    foreach ($empresas as $empresa) {
                        $row[] = number_format($datosMes['empresas'][$empresa->id][$clave], 2, '.', '');
                    }
                    $row[] = number_format($datosMes['total'][$clave], 2, '.', '');
                    fputcsv($handle, $row, ';');
                }

                $rowTotal = ['Total ' . $anio];
                foreach ($empresas as $empresa) {
                    $sumaEmpresa = collect($meses)->sum(fn ($m) => $m['empresas'][$empresa->id][$clave]);
                    $rowTotal[] = number_format($sumaEmpresa, 2, '.', '');
                }
                $rowTotal[] = number_format($totalesAnio[$clave], 2, '.', '');
                fputcsv($handle, $rowTotal, ';');
                fputcsv($handle, [], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        [$empresas, $meses, $totalesAnio] = $this->calcularDatos($this->filtroAnio);

        return view('livewire.reportes.reporte-iva', [
            'empresas'    => $empresas,
            'meses'       => $meses,
            'totalesAnio' => $totalesAnio,
            'anio'        => $this->filtroAnio,
        ]);
    }
}
