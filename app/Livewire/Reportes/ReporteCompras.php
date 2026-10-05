<?php

namespace App\Livewire\Reportes;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Rubro;
use App\Traits\CambiaEmpresaDesdeQuery;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReporteCompras extends Component
{
    use CambiaEmpresaDesdeQuery;

    public string $desde = '';

    public string $hasta = '';

    public string $actividad = '';

    public string $rubro = '';

    public string $proveedor = '';

    public function mount(): void
    {
        $this->switchEmpresaDesdeQuery();
        $this->desde = now()->startOfYear()->toDateString();
        $this->hasta = now()->toDateString();
    }

    private function resumen()
    {
        $this->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
            'actividad' => 'nullable|in:sin_clasificar,'.implode(',', array_keys(Compra::ACTIVIDADES)),
            'rubro' => 'nullable|in:sin_clasificar,'.implode(',', array_keys(Rubro::opciones())),
        ]);

        return Compra::query()->with(['lote', 'campana'])
            ->where('estado', '!=', 'cancelada')
            ->when($this->desde, fn ($q) => $q->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($q) => $q->whereDate('fecha', '<=', $this->hasta))
            ->when($this->proveedor, fn ($q) => $q->where('id_proveedor', $this->proveedor))
            ->when($this->actividad === 'sin_clasificar', fn ($q) => $q->where(fn ($q) => $q->whereNull('actividad')->orWhere('actividad', '')))
            ->when($this->actividad && $this->actividad !== 'sin_clasificar', fn ($q) => $q->where('actividad', $this->actividad))
            ->when($this->rubro === 'sin_clasificar', fn ($q) => $q->where(fn ($q) => $q->whereNull('rubro')->orWhereIn('rubro', ['', 'otro'])))
            ->when($this->rubro && $this->rubro !== 'sin_clasificar', fn ($q) => $q->where('rubro', $this->rubro))
            ->selectRaw("COALESCE(NULLIF(rubro, ''), 'otro') AS rubro, COALESCE(NULLIF(actividad, ''), '') AS actividad, id_lote, id_campana, COUNT(*) AS cantidad, SUM(subtotal) AS neto, SUM(COALESCE(iva_importe,0)) AS iva, SUM(total) AS importe")
            ->groupByRaw("COALESCE(NULLIF(rubro, ''), 'otro'), COALESCE(NULLIF(actividad, ''), ''), id_lote, id_campana")
            ->orderBy('rubro')->orderBy('actividad')->get();
    }

    public function exportar()
    {
        Gate::authorize('reportes.economicos.ver');
        $filas = $this->resumen();
        $rubros = Rubro::opciones();

        return response()->streamDownload(function () use ($filas, $rubros) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Rubro', 'Actividad / Imputación', 'Lote', 'Campaña', 'Comprobantes', 'Neto', 'IVA', 'Total'], ';');
            foreach ($filas as $fila) {
                $datos = [$rubros[$fila->rubro] ?? $fila->rubro, Compra::ACTIVIDADES[$fila->actividad] ?? 'Sin clasificar', $fila->lote?->nombre ?? '', $fila->campana?->nombre ?? '', $fila->cantidad, $fila->neto, $fila->iva, $fila->importe];
                foreach (array_slice($datos, 0, 4) as $i => $texto) {
                    if (preg_match('/^[=+@\-\t\r]/', $texto)) {
                        $datos[$i] = "'".$texto;
                    }
                }
                fputcsv($f, $datos, ';');
            }
            fclose($f);
        }, 'informe-compras.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reportes.reporte-compras', [
            'filas' => $this->resumen(),
            'rubros' => Rubro::opciones(),
            'actividades' => Compra::ACTIVIDADES,
            'proveedores' => Proveedor::orderBy('nombre')->get(),
        ]);
    }
}
