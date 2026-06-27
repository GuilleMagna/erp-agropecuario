<?php

namespace App\Livewire\Insumos;

use App\Models\Insumo;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCatalogo extends Component
{
    use WithPagination;

    public string $busqueda    = '';
    public string $filtroTipo  = '';
    public string $filtroActivo = '';

    public bool    $modalAbierto     = false;
    public bool    $modoEdicion      = false;
    public ?string $insumoEditandoId = null;

    public string $nombre             = '';
    public string $codigo             = '';
    public string $tipo               = 'otro';
    public string $unidad             = 'unidad';
    public string $marca              = '';
    public string $descripcion        = '';
    public string $stock_minimo       = '';
    public string $precio_referencia  = '';
    public bool   $activo             = true;

    protected function rules(): array
    {
        return [
            'nombre'            => 'required|string|max:150',
            'codigo'            => 'nullable|string|max:30',
            'tipo'              => 'required|in:' . implode(',', array_keys(Insumo::TIPOS)),
            'unidad'            => 'required|in:' . implode(',', array_keys(Insumo::UNIDADES)),
            'marca'             => 'nullable|string|max:100',
            'descripcion'       => 'nullable|string',
            'stock_minimo'      => 'nullable|numeric|min:0',
            'precio_referencia' => 'nullable|numeric|min:0',
            'activo'            => 'boolean',
        ];
    }

    public function updatedBusqueda(): void   { $this->resetPage(); }
    public function updatedFiltroTipo(): void  { $this->resetPage(); }
    public function updatedFiltroActivo(): void { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('insumos.catalogo.gestionar');
        $this->resetForm();
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('insumos.catalogo.gestionar');
        $insumo = Insumo::findOrFail($id);
        $this->insumoEditandoId   = $id;
        $this->nombre             = $insumo->nombre;
        $this->codigo             = $insumo->codigo ?? '';
        $this->tipo               = $insumo->tipo;
        $this->unidad             = $insumo->unidad;
        $this->marca              = $insumo->marca ?? '';
        $this->descripcion        = $insumo->descripcion ?? '';
        $this->stock_minimo       = $insumo->stock_minimo !== null ? (string) $insumo->stock_minimo : '';
        $this->precio_referencia  = $insumo->precio_referencia !== null ? (string) $insumo->precio_referencia : '';
        $this->activo             = (bool) $insumo->activo;
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
        Gate::authorize('insumos.catalogo.gestionar');
        $this->validate();

        $data = [
            'nombre'            => $this->nombre,
            'codigo'            => $this->codigo ?: null,
            'tipo'              => $this->tipo,
            'unidad'            => $this->unidad,
            'marca'             => $this->marca ?: null,
            'descripcion'       => $this->descripcion ?: null,
            'stock_minimo'      => $this->stock_minimo !== '' ? (float) $this->stock_minimo : null,
            'precio_referencia' => $this->precio_referencia !== '' ? (float) $this->precio_referencia : null,
            'activo'            => $this->activo,
        ];

        if ($this->modoEdicion) {
            Insumo::findOrFail($this->insumoEditandoId)->update($data);
            session()->flash('success', 'Insumo actualizado correctamente.');
        } else {
            Insumo::create($data);
            session()->flash('success', 'Insumo creado correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('insumos.catalogo.gestionar');
        $insumo = Insumo::findOrFail($id);
        $insumo->update(['activo' => !$insumo->activo]);
        session()->flash('success', $insumo->activo ? 'Insumo reactivado.' : 'Insumo dado de baja.');
    }

    private function resetForm(): void
    {
        $this->insumoEditandoId  = null;
        $this->nombre            = '';
        $this->codigo            = '';
        $this->tipo              = 'otro';
        $this->unidad            = 'unidad';
        $this->marca             = '';
        $this->descripcion       = '';
        $this->stock_minimo      = '';
        $this->precio_referencia = '';
        $this->activo            = true;
        $this->resetValidation();
    }

    public function render()
    {
        $insumos = Insumo::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) =>
                $q->where('nombre', 'like', "%{$this->busqueda}%")
                  ->orWhere('codigo', 'like', "%{$this->busqueda}%")
                  ->orWhere('marca', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroTipo, fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroActivo !== '', fn ($q) => $q->where('activo', (bool) $this->filtroActivo))
            ->withSum(
                ['movimientosInsumos as total_entradas' => fn ($q) => $q->whereIn('tipo', ['entrada', 'ajuste_positivo'])],
                'cantidad'
            )
            ->withSum(
                ['movimientosInsumos as total_salidas' => fn ($q) => $q->whereIn('tipo', ['salida', 'ajuste_negativo'])],
                'cantidad'
            )
            ->orderBy('nombre')
            ->paginate(20);

        return view('livewire.insumos.gestion-catalogo', [
            'insumos' => $insumos,
            'tipos'   => Insumo::TIPOS,
            'unidades' => Insumo::UNIDADES,
        ]);
    }
}
