<?php

namespace App\Livewire\Feedlot;

use App\Models\ConsumoAlimento;
use App\Models\Corral;
use App\Models\Establecimiento;
use App\Models\Insumo;
use App\Models\Tropa;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionConsumos extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // Filtros
    public string $filtroCorral          = '';
    public string $filtroTropa           = '';
    public string $filtroEstablecimiento = '';
    public string $filtroFechaDesde      = '';
    public string $filtroFechaHasta      = '';

    // Modal
    public bool    $modalAbierto       = false;
    public ?string $consumoEditandoId  = null;

    // Campos del formulario
    public string $fecha               = '';
    public string $idCorral            = '';
    public string $idTropa             = '';
    public string $idEstablecimiento   = '';
    public string $idInsumo            = '';
    public string $descripcionAlimento = '';
    public string $cantidadKg          = '';
    public string $costoUnitario       = '';
    public string $costoTotal          = '';
    public string $observaciones       = '';

    public function mount(): void
    {
        $this->fecha            = now()->format('Y-m-d');
        $this->filtroFechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroFechaHasta = now()->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'fecha'               => 'required|date',
            'idCorral'            => 'nullable|exists:corrales,id',
            'idTropa'             => 'nullable|exists:tropas,id',
            'idEstablecimiento'   => 'nullable|exists:establecimientos,id',
            'idInsumo'            => 'nullable|exists:insumos,id',
            'descripcionAlimento' => 'required_without:idInsumo|nullable|string|max:150',
            'cantidadKg'          => 'required|numeric|min:0.01',
            'costoUnitario'       => 'nullable|numeric|min:0',
            'costoTotal'          => 'nullable|numeric|min:0',
            'observaciones'       => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'fecha.required'                  => 'La fecha es obligatoria.',
        'cantidadKg.required'             => 'La cantidad es obligatoria.',
        'cantidadKg.min'                  => 'La cantidad debe ser mayor a 0.',
        'descripcionAlimento.required_without' => 'Ingresá el alimento o seleccionalo del catálogo.',
    ];

    public function updatingFiltroCorral(): void          { $this->resetPage(); $this->filtroTropa = ''; }
    public function updatingFiltroTropa(): void           { $this->resetPage(); }
    public function updatingFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatingFiltroFechaDesde(): void      { $this->resetPage(); }
    public function updatingFiltroFechaHasta(): void      { $this->resetPage(); }

    public function updatedIdCorral(string $value): void
    {
        $this->idTropa = '';
    }

    public function updatedIdInsumo(string $value): void
    {
        if ($value) {
            $insumo = Insumo::find($value);
            if ($insumo) {
                $this->descripcionAlimento = $insumo->nombre;
                if ($insumo->precio_referencia) {
                    $this->costoUnitario = number_format((float)$insumo->precio_referencia, 4, '.', '');
                    $this->calcularCostoTotal();
                }
            }
        }
    }

    public function updatedCantidadKg(): void  { $this->calcularCostoTotal(); }
    public function updatedCostoUnitario(): void { $this->calcularCostoTotal(); }

    private function calcularCostoTotal(): void
    {
        $cantidad = (float)($this->cantidadKg ?: 0);
        $costo    = (float)($this->costoUnitario ?: 0);
        if ($cantidad > 0 && $costo > 0) {
            $this->costoTotal = number_format($cantidad * $costo, 2, '.', '');
        }
    }

    public function abrirModalRegistrar(): void
    {
        Gate::authorize('feedlot.consumos.registrar');
        $this->limpiarFormulario();
        $this->fecha        = now()->format('Y-m-d');
        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('feedlot.consumos.registrar');
        $this->validate();

        ConsumoAlimento::create([
            'id_empresa'          => auth()->user()->id_empresa,
            'id_corral'           => $this->idCorral ?: null,
            'id_tropa'            => $this->idTropa ?: null,
            'id_establecimiento'  => $this->idEstablecimiento ?: null,
            'id_insumo'           => $this->idInsumo ?: null,
            'fecha'               => $this->fecha,
            'descripcion_alimento'=> $this->descripcionAlimento ?: null,
            'cantidad_kg'         => (float)$this->cantidadKg,
            'costo_unitario'      => $this->costoUnitario !== '' ? (float)$this->costoUnitario : null,
            'costo_total'         => $this->costoTotal !== '' ? (float)$this->costoTotal : null,
            'observaciones'       => $this->observaciones ?: null,
        ]);

        session()->flash('success', 'Consumo registrado correctamente.');
        $this->cerrarModal();
    }

    public function eliminar(string $id): void
    {
        Gate::authorize('feedlot.consumos.registrar');
        $consumo = ConsumoAlimento::findOrFail($id);
        $consumo->delete();
        session()->flash('success', 'Registro eliminado.');
    }

    public function exportarCsv(): mixed
    {
        Gate::authorize('feedlot.consumos.registrar');

        $desde    = $this->filtroFechaDesde;
        $hasta    = $this->filtroFechaHasta;
        $filename = 'consumos-feedlot-' . now()->format('Y-m-d') . '.csv';

        $registros = ConsumoAlimento::query()
            ->with(['corral', 'tropa', 'insumo'])
            ->when($desde, fn($q) => $q->where('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->where('fecha', '<=', $hasta))
            ->when($this->filtroCorral, fn($q) => $q->where('id_corral', $this->filtroCorral))
            ->when($this->filtroTropa, fn($q) => $q->where('id_tropa', $this->filtroTropa))
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->streamDownload(function () use ($registros) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Fecha', 'Corral', 'Tropa', 'Alimento', 'Cantidad (kg)', 'Costo unit. ($/kg)', 'Costo total ($)', 'Observaciones'], ';');
            foreach ($registros as $r) {
                fputcsv($handle, [
                    $r->fecha->format('d/m/Y'),
                    $r->corral?->nombre ?? '—',
                    $r->tropa?->nombre ?? '—',
                    $r->nombre_alimento,
                    number_format((float)$r->cantidad_kg, 2, '.', ''),
                    $r->costo_unitario !== null ? number_format((float)$r->costo_unitario, 4, '.', '') : '',
                    $r->costo_total !== null ? number_format((float)$r->costo_total, 2, '.', '') : '',
                    $r->observaciones ?? '',
                ], ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'consumoEditandoId', 'idCorral', 'idTropa', 'idEstablecimiento',
            'idInsumo', 'descripcionAlimento', 'cantidadKg',
            'costoUnitario', 'costoTotal', 'observaciones',
        ]);
        $this->fecha = now()->format('Y-m-d');
        $this->resetValidation();
    }

    public function render()
    {
        $desde = $this->filtroFechaDesde;
        $hasta = $this->filtroFechaHasta;

        $consumos = ConsumoAlimento::query()
            ->with(['corral', 'tropa', 'insumo'])
            ->when($desde, fn($q) => $q->where('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->where('fecha', '<=', $hasta))
            ->when($this->filtroCorral, fn($q) => $q->where('id_corral', $this->filtroCorral))
            ->when($this->filtroTropa, fn($q) => $q->where('id_tropa', $this->filtroTropa))
            ->when($this->filtroEstablecimiento, fn($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->orderBy('fecha', 'desc')
            ->paginate(20);

        // KPIs de hoy
        $hoy          = now()->format('Y-m-d');
        $totalKgHoy   = ConsumoAlimento::whereDate('fecha', $hoy)->sum('cantidad_kg');
        $totalCostoHoy= ConsumoAlimento::whereDate('fecha', $hoy)->sum('costo_total');
        $registrosHoy = ConsumoAlimento::whereDate('fecha', $hoy)->count();

        // Totales del filtro actual (sin paginar)
        $baseQuery    = ConsumoAlimento::query()
            ->when($desde, fn($q) => $q->where('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->where('fecha', '<=', $hasta))
            ->when($this->filtroCorral, fn($q) => $q->where('id_corral', $this->filtroCorral))
            ->when($this->filtroTropa, fn($q) => $q->where('id_tropa', $this->filtroTropa));

        $totalKgFiltro    = (clone $baseQuery)->sum('cantidad_kg');
        $totalCostoFiltro = (clone $baseQuery)->sum('costo_total');

        $corrales         = Corral::activos()->orderBy('nombre')->get();
        $insumos          = Insumo::activos()->orderBy('nombre')->get();
        $establecimientos = Establecimiento::orderBy('nombre')->get();

        // Tropas filtradas por corral seleccionado en filtro/modal
        $tropasModal  = $this->idCorral
            ? Tropa::where('id_corral', $this->idCorral)->where('estado', 'activa')->orderBy('nombre')->get()
            : Tropa::activas()->orderBy('nombre')->get();
        $tropasFilter = $this->filtroCorral
            ? Tropa::where('id_corral', $this->filtroCorral)->orderBy('nombre')->get()
            : Tropa::orderBy('nombre')->get();

        return view('livewire.feedlot.gestion-consumos', compact(
            'consumos', 'corrales', 'insumos', 'establecimientos',
            'tropasModal', 'tropasFilter',
            'totalKgHoy', 'totalCostoHoy', 'registrosHoy',
            'totalKgFiltro', 'totalCostoFiltro',
        ));
    }
}
