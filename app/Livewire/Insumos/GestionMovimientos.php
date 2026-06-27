<?php

namespace App\Livewire\Insumos;

use App\Models\Establecimiento;
use App\Models\Insumo;
use App\Models\MovimientoInsumo;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionMovimientos extends Component
{
    use WithPagination;

    public string $busqueda              = '';
    public string $filtroInsumo          = '';
    public string $filtroTipo            = '';
    public string $filtroEstablecimiento = '';
    public string $filtroFechaDesde      = '';
    public string $filtroFechaHasta      = '';

    public bool    $modalAbierto           = false;
    public bool    $modoEdicion            = false;
    public ?string $movimientoEditandoId   = null;

    public string $id_insumo          = '';
    public string $id_establecimiento = '';
    public string $tipo               = 'entrada';
    public string $motivo             = 'compra';
    public string $cantidad           = '';
    public string $precio_unitario    = '';
    public string $importe_total      = '';
    public string $fecha              = '';
    public string $numero_remito      = '';
    public string $proveedor          = '';
    public string $observaciones      = '';

    protected function rules(): array
    {
        $tiposValidos   = implode(',', array_keys(MovimientoInsumo::TIPOS));
        $motivosValidos = implode(',', array_keys(MovimientoInsumo::MOTIVOS));

        return [
            'id_insumo'          => 'required|exists:insumos,id',
            'id_establecimiento' => 'nullable|exists:establecimientos,id',
            'tipo'               => 'required|in:' . $tiposValidos,
            'motivo'             => 'required|in:' . $motivosValidos,
            'cantidad'           => 'required|numeric|min:0.01',
            'precio_unitario'    => 'nullable|numeric|min:0',
            'importe_total'      => 'nullable|numeric|min:0',
            'fecha'              => 'required|date',
            'numero_remito'      => 'nullable|string|max:50',
            'proveedor'          => 'nullable|string|max:150',
            'observaciones'      => 'nullable|string',
        ];
    }

    public function updatedBusqueda(): void              { $this->resetPage(); }
    public function updatedFiltroInsumo(): void          { $this->resetPage(); }
    public function updatedFiltroTipo(): void            { $this->resetPage(); }
    public function updatedFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatedFiltroFechaDesde(): void      { $this->resetPage(); }
    public function updatedFiltroFechaHasta(): void      { $this->resetPage(); }

    public function updatedTipo(): void
    {
        $this->motivo = '';
    }

    public function updatedCantidad(): void
    {
        $this->calcularImporte();
    }

    public function updatedPrecioUnitario(): void
    {
        $this->calcularImporte();
    }

    private function calcularImporte(): void
    {
        if ($this->cantidad !== '' && $this->precio_unitario !== '') {
            $this->importe_total = (string) round(
                (float) $this->cantidad * (float) $this->precio_unitario,
                2
            );
        }
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('insumos.movimientos.registrar');
        $this->resetForm();
        $this->fecha       = now()->format('Y-m-d');
        $this->modoEdicion = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('insumos.movimientos.registrar');
        $m = MovimientoInsumo::findOrFail($id);
        $this->movimientoEditandoId = $id;
        $this->id_insumo           = $m->id_insumo;
        $this->id_establecimiento  = $m->id_establecimiento ?? '';
        $this->tipo                = $m->tipo;
        $this->motivo              = $m->motivo;
        $this->cantidad            = (string) $m->cantidad;
        $this->precio_unitario     = $m->precio_unitario !== null ? (string) $m->precio_unitario : '';
        $this->importe_total       = $m->importe_total !== null ? (string) $m->importe_total : '';
        $this->fecha               = $m->fecha->format('Y-m-d');
        $this->numero_remito       = $m->numero_remito ?? '';
        $this->proveedor           = $m->proveedor ?? '';
        $this->observaciones       = $m->observaciones ?? '';
        $this->modoEdicion  = true;
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function guardar(): void
    {
        Gate::authorize('insumos.movimientos.registrar');
        $this->validate();

        $data = [
            'id_insumo'          => $this->id_insumo,
            'id_establecimiento' => $this->id_establecimiento ?: null,
            'tipo'               => $this->tipo,
            'motivo'             => $this->motivo,
            'cantidad'           => (float) $this->cantidad,
            'precio_unitario'    => $this->precio_unitario !== '' ? (float) $this->precio_unitario : null,
            'importe_total'      => $this->importe_total !== '' ? (float) $this->importe_total : null,
            'fecha'              => $this->fecha,
            'numero_remito'      => $this->numero_remito ?: null,
            'proveedor'          => $this->proveedor ?: null,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            MovimientoInsumo::findOrFail($this->movimientoEditandoId)->update($data);
            session()->flash('success', 'Movimiento actualizado correctamente.');
        } else {
            MovimientoInsumo::create($data);
            session()->flash('success', 'Movimiento registrado correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->movimientoEditandoId = null;
        $this->id_insumo           = '';
        $this->id_establecimiento  = '';
        $this->tipo                = 'entrada';
        $this->motivo              = 'compra';
        $this->cantidad            = '';
        $this->precio_unitario     = '';
        $this->importe_total       = '';
        $this->fecha               = '';
        $this->numero_remito       = '';
        $this->proveedor           = '';
        $this->observaciones       = '';
        $this->resetValidation();
    }

    public function render()
    {
        $movimientos = MovimientoInsumo::query()
            ->when($this->busqueda, fn ($q) => $q->whereHas('insumo', fn ($q) =>
                $q->where('nombre', 'like', "%{$this->busqueda}%")
                  ->orWhere('codigo', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroInsumo, fn ($q) => $q->where('id_insumo', $this->filtroInsumo))
            ->when($this->filtroTipo, fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroFechaDesde, fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta))
            ->with(['insumo', 'establecimiento'])
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $insumosOpciones  = Insumo::activos()->orderBy('nombre')->get();
        $establecimientos = Establecimiento::orderBy('nombre')->get();

        $motivosFiltrados = collect(MovimientoInsumo::MOTIVOS)
            ->only(MovimientoInsumo::MOTIVOS_POR_TIPO[$this->tipo] ?? array_keys(MovimientoInsumo::MOTIVOS))
            ->toArray();

        return view('livewire.insumos.gestion-movimientos', [
            'movimientos'       => $movimientos,
            'insumosOpciones'   => $insumosOpciones,
            'establecimientos'  => $establecimientos,
            'tipos'             => MovimientoInsumo::TIPOS,
            'todosMotivos'      => MovimientoInsumo::MOTIVOS,
            'motivosFiltrados'  => $motivosFiltrados,
        ]);
    }
}
