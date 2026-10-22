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

    public string $zona = '';

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
            'zona' => 'nullable|in:sin_clasificar,'.implode(',', array_keys(Compra::ZONAS)),
            'rubro' => 'nullable|in:sin_clasificar,'.implode(',', array_keys(Rubro::opciones())),
        ]);

        return Compra::query()->with(['lote', 'campana'])
            ->where('estado', '!=', 'cancelada')
            ->when($this->desde, fn ($q) => $q->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($q) => $q->whereDate('fecha', '<=', $this->hasta))
            ->when($this->proveedor, fn ($q) => $q->where('id_proveedor', $this->proveedor))
            ->when($this->actividad === 'sin_clasificar', fn ($q) => $q->where(fn ($q) => $q->whereNull('actividad')->orWhere('actividad', '')))
            ->when($this->actividad && $this->actividad !== 'sin_clasificar', fn ($q) => $q->where('actividad', $this->actividad))
            ->when($this->zona === 'sin_clasificar', fn ($q) => $q->where(fn ($q) => $q->whereNull('zona')->orWhere('zona', '')))
            ->when($this->zona === 'santa_fe', fn ($q) => $q->whereIn('zona', ['santa_fe', 'el_trebol']))
            ->when($this->zona && ! in_array($this->zona, ['sin_clasificar', 'santa_fe']), fn ($q) => $q->where('zona', $this->zona))
            ->when($this->rubro === 'sin_clasificar', fn ($q) => $q->where(fn ($q) => $q->whereNull('rubro')->orWhere('rubro', '')))
            ->when($this->rubro && $this->rubro !== 'sin_clasificar', fn ($q) => $q->where('rubro', $this->rubro))
            ->selectRaw("COALESCE(NULLIF(rubro, ''), 'sin_clasificar') AS rubro, COALESCE(NULLIF(actividad, ''), '') AS actividad, CASE WHEN zona = 'el_trebol' THEN 'santa_fe' ELSE COALESCE(zona, '') END AS zona, id_lote, id_campana, COUNT(*) AS cantidad, SUM(subtotal) AS neto, SUM(COALESCE(iva_importe,0)) AS iva, SUM(total) AS importe")
            ->groupBy('rubro', 'actividad', 'zona', 'id_lote', 'id_campana')
            ->orderBy('rubro')->orderBy('actividad')->get()
            ->groupBy(fn ($fila) => json_encode([$fila->rubro, $fila->actividad, $fila->zona, $fila->id_lote, $fila->id_campana]))
            ->map(function ($grupo) {
                $fila = $grupo->first();
                foreach (['cantidad', 'neto', 'iva', 'importe'] as $campo) {
                    $fila->{$campo} = $grupo->sum($campo);
                }

                return $fila;
            })->values();
    }

    public function exportar()
    {
        Gate::authorize('reportes.economicos.ver');
        $filas = $this->resumen();
        $rubros = Rubro::opciones();

        return response()->streamDownload(function () use ($filas, $rubros) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Rubro', 'Actividad / Imputación', 'Lote', 'Campaña', 'Zona', 'Comprobantes', 'Neto', 'IVA', 'Total'], ';');
            foreach ($filas as $fila) {
                $datos = [$fila->rubro === 'sin_clasificar' ? 'Sin rubro' : ($rubros[$fila->rubro] ?? $fila->rubro), Compra::ACTIVIDADES[$fila->actividad] ?? 'Sin clasificar', $fila->lote?->nombre ?? '', $fila->campana?->nombre ?? '', Compra::ZONAS[$fila->zona] ?? 'Sin zona', $fila->cantidad, $fila->neto, $fila->iva, $fila->importe];
                foreach (array_slice($datos, 0, 5) as $i => $texto) {
                    if (preg_match('/^[=+@\-\t\r]/', $texto)) {
                        $datos[$i] = "'".$texto;
                    }
                }
                fputcsv($f, $datos, ';');
            }
            fclose($f);
        }, 'informe-compras.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function enlaceCompras(?string $rubro = null, ?string $actividad = null, ?string $lote = null, ?string $campana = null, ?string $zona = null): string
    {
        return route('compras.index', array_filter([
            'empresa' => Compra::resolverEmpresaActiva(),
            'filtroFechaDesde' => $this->desde,
            'filtroFechaHasta' => $this->hasta,
            'filtroProveedor' => $this->proveedor,
            'filtroRubro' => $rubro ?? $this->rubro,
            'filtroActividad' => ($actividad ?? $this->actividad) === 'sin_clasificar' ? 'sin_imputar' : ($actividad ?? $this->actividad),
            'filtroZona' => $zona ?? $this->zona,
            'filtroLote' => $lote,
            'filtroCampana' => $campana,
            'filtroExcluirCanceladas' => true,
        ], fn ($valor) => $valor !== null && $valor !== ''));
    }

    private function serie($filas, string $campo, array $etiquetas): array
    {
        $colores = ['#198754', '#0d6efd', '#fd7e14', '#6f42c1', '#0dcaf0', '#d63384', '#64748b', '#a16207'];

        return $filas->groupBy(fn ($fila) => $campo === 'rubro'
            ? ($fila->rubro ?: 'sin_clasificar')
            : ($fila->{$campo} ?: 'sin_clasificar'))
            ->map(function ($grupo, $codigo) use ($campo, $etiquetas) {
                return [
                    'codigo' => $codigo,
                    'nombre' => $codigo === 'sin_clasificar' ? match ($campo) {
                        'rubro' => 'Sin rubro', 'zona' => 'Sin zona', default => 'Sin imputación'
                    } : ($etiquetas[$codigo] ?? $codigo),
                    'importe' => round((float) $grupo->sum('importe'), 2),
                    'cantidad' => (int) $grupo->sum('cantidad'),
                    'url' => match ($campo) {
                        'rubro' => $this->enlaceCompras($codigo), 'zona' => $this->enlaceCompras(null, null, null, null, $codigo), default => $this->enlaceCompras(null, $codigo)
                    },
                ];
            })->sortByDesc('importe')->values()
            ->map(fn ($grupo, $i) => $grupo + ['color' => $colores[$i % count($colores)]])->all();
    }

    public function render()
    {
        $filas = $this->resumen();
        $porRubro = $this->serie($filas, 'rubro', Rubro::opciones());

        return view('livewire.reportes.reporte-compras', [
            'filas' => $filas,
            'porRubro' => $porRubro,
            'porZona' => $this->serie($filas, 'zona', Compra::ZONAS),
            'zonas' => Compra::ZONAS,
            'porActividad' => $this->serie($filas, 'actividad', Compra::ACTIVIDADES),
            'sinRubro' => collect($porRubro)->firstWhere('codigo', 'sin_clasificar'),
            'rubros' => Rubro::opciones(),
            'actividades' => Compra::ACTIVIDADES,
            'proveedores' => Proveedor::orderBy('nombre')->get(),
        ]);
    }
}
