<?php

namespace App\Livewire\Reportes;

use App\Models\Compra;
use App\Models\GastoNoArca;
use App\Models\Rubro;
use App\Models\Ubicacion;
use App\Traits\CambiaEmpresaDesdeQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

class ReporteGastos extends Component
{
    use CambiaEmpresaDesdeQuery;

    #[Url]
    public string $desde = '';

    #[Url]
    public string $hasta = '';

    #[Url]
    public string $rubro = '';

    #[Url]
    public string $origen = '';

    #[Url]
    public string $zona = '';

    #[Url]
    public bool $incluirGenerales = true;

    public function mount(): void
    {
        $this->switchEmpresaDesdeQuery();
        $this->desde = $this->desde ?: now()->format('Y').'-01';
        $this->hasta = $this->hasta ?: now()->format('Y-m');
    }

    private function datos()
    {
        Gate::authorize('finanzas.gastos.gestionar');
        Gate::authorize('compras.ver');
        $this->validate(['desde' => 'required|date_format:Y-m', 'hasta' => 'required|date_format:Y-m|after_or_equal:desde', 'origen' => 'nullable|in:compras,no_arca', 'zona' => 'nullable|in:sin_clasificar,'.implode(',', array_keys(Ubicacion::opciones()))]);
        $desde = Carbon::parse($this->desde.'-01');
        $hasta = Carbon::parse($this->hasta.'-01')->endOfMonth();
        $empresa = Compra::resolverEmpresaActiva();
        abort_unless($empresa, 403);
        $filas = collect();
        if ($this->origen !== 'no_arca') {
            $filas = Compra::where('estado', '!=', 'cancelada')->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
                ->selectRaw("COALESCE(NULLIF(rubro, ''), 'sin_clasificar') AS rubro, CASE WHEN zona = 'el_trebol' THEN 'santa_fe' ELSE COALESCE(NULLIF(zona, ''), 'sin_clasificar') END AS zona, COUNT(*) AS cantidad, SUM(total) AS importe")
                ->groupBy('rubro', 'zona')->get()->map(fn ($fila) => ['rubro' => $fila->rubro, 'origen' => 'compras', 'zona' => $fila->zona, 'cantidad' => (int) $fila->cantidad, 'importe' => (float) $fila->importe]);
        }
        if ($this->origen !== 'compras') {
            $pagos = DB::table('pagos_gasto_no_arca as p')->join('gastos_no_arca as g', 'g.id', '=', 'p.id_gasto')
                ->whereBetween('p.mes', [$desde->toDateString(), $hasta->toDateString()])
                ->where(fn ($q) => $q->where('g.id_empresa', $empresa)->when($this->incluirGenerales, fn ($q) => $q->orWhereNull('g.id_empresa')))
                ->selectRaw("COALESCE(NULLIF(g.rubro, ''), 'sin_clasificar') AS rubro, COALESCE(NULLIF(g.zona, ''), 'sin_clasificar') AS zona, g.id_empresa, COUNT(*) AS cantidad, SUM(p.importe) AS importe")
                ->groupBy('g.rubro', 'g.zona', 'g.id_empresa')->get()
                ->map(fn ($fila) => ['rubro' => $fila->rubro, 'origen' => $fila->id_empresa ? 'no_arca' : 'generales', 'zona' => $fila->zona, 'cantidad' => (int) $fila->cantidad, 'importe' => (float) $fila->importe]);
            $filas = $filas->concat($pagos);
        }
        $filas = $filas->groupBy(fn ($fila) => $fila['rubro'].'|'.$fila['origen'].'|'.$fila['zona'])->map(fn ($grupo) => array_replace($grupo->first(), ['cantidad' => $grupo->sum('cantidad'), 'importe' => round($grupo->sum('importe'), 2)]))->values();

        return $filas->when($this->zona, fn ($c) => $c->whereIn('zona', Ubicacion::descendientes($this->zona)))->when($this->rubro, fn ($c) => $c->where('rubro', $this->rubro))->values();
    }

    public function enlace(string $rubro, string $origen, ?string $zona = null): string
    {
        if ($origen === 'compras') {
            return route('compras.index', ['empresa' => Compra::resolverEmpresaActiva(), 'filtroZona' => $zona ?? $this->zona, 'filtroRubro' => $rubro === 'sin_clasificar' ? 'sin_clasificar' : $rubro, 'filtroFechaDesde' => $this->desde.'-01', 'filtroFechaHasta' => Carbon::parse($this->hasta.'-01')->endOfMonth()->toDateString(), 'filtroExcluirCanceladas' => true]);
        }

        return route('finanzas.gastos.index', ['vistaActiva' => 'catalogo', 'filtroRubro' => $rubro, 'filtroZona' => $zona ?? $this->zona]);
    }

    public function exportar()
    {
        $filas = $this->datos();
        $rubros = array_replace(Rubro::opciones(), GastoNoArca::RUBROS);

        return response()->streamDownload(function () use ($filas, $rubros) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Rubro', 'Ubicación', 'Origen', 'Cantidad', 'Importe'], ';');
            foreach ($filas as $fila) {
                $nombre = $rubros[$fila['rubro']] ?? 'Sin rubro';
                if (preg_match('/^[=+@\-\t\r]/', $nombre)) {
                    $nombre = "'".$nombre;
                }
                $ubicacion = Ubicacion::opciones()[$fila['zona']] ?? 'Sin ubicación';
                if (preg_match('/^[=+@\-\t\r]/', $ubicacion)) {
                    $ubicacion = "'".$ubicacion;
                }
                fputcsv($f, [$nombre, $ubicacion, $fila['origen'], $fila['cantidad'], $fila['importe']], ';');
            }
            fclose($f);
        }, 'informe-gastos.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $filas = $this->datos();
        $rubros = array_replace(Rubro::opciones(), GastoNoArca::RUBROS);
        asort($rubros, SORT_NATURAL | SORT_FLAG_CASE);
        $serie = $filas->groupBy('rubro')->map(fn ($grupo, $codigo) => [
            'codigo' => $codigo, 'nombre' => $rubros[$codigo] ?? 'Sin rubro', 'importe' => round($grupo->sum('importe'), 2),
            'cantidad' => $grupo->sum('cantidad'), 'url' => route('reportes.gastos', ['empresa' => Compra::resolverEmpresaActiva(), 'zona' => $this->zona, 'origen' => $this->origen, 'incluirGenerales' => $this->incluirGenerales, 'rubro' => $codigo, 'desde' => $this->desde, 'hasta' => $this->hasta]),
        ])->sortByDesc('importe')->values()->map(fn ($grupo, $i) => $grupo + ['color' => ['#198754', '#0d6efd', '#fd7e14', '#6f42c1', '#d63384', '#64748b'][$i % 6]])->all();

        $zonas = Ubicacion::opciones();
        $agrupaciones = Ubicacion::agrupaciones($this->zona ?: null);
        $porZona = $filas->groupBy(fn ($fila) => $agrupaciones[$fila['zona']] ?? $fila['zona'])->map(fn ($grupo, $codigo) => [
            'codigo' => $codigo, 'nombre' => $zonas[$codigo] ?? 'Sin zona', 'importe' => round($grupo->sum('importe'), 2),
            'cantidad' => $grupo->sum('cantidad'), 'url' => route('reportes.gastos', ['zona' => $codigo, 'rubro' => $this->rubro, 'desde' => $this->desde, 'hasta' => $this->hasta, 'origen' => $this->origen, 'incluirGenerales' => $this->incluirGenerales, 'empresa' => Compra::resolverEmpresaActiva()]),
        ])->sortByDesc('importe')->values()->map(fn ($grupo, $i) => $grupo + ['color' => ['#198754', '#0d6efd', '#fd7e14', '#6f42c1'][$i % 4]])->all();

        return view('livewire.reportes.reporte-gastos', compact('filas', 'rubros', 'serie', 'zonas', 'porZona'));
    }
}
