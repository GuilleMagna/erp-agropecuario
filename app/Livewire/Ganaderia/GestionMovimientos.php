<?php

namespace App\Livewire\Ganaderia;

use App\Models\Animal;
use App\Models\Establecimiento;
use App\Models\Movimiento;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionMovimientos extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda              = '';
    public string $filtroEstablecimiento = '';
    public string $filtroTipo            = '';
    public string $filtroCategoria       = '';

    // Estado modal
    public bool    $modalAbierto         = false;
    public bool    $modoEdicion          = false;
    public ?string $movimientoEditandoId = null;

    // Campos del formulario
    public string $id_establecimiento  = '';
    public string $tipo                = 'compra';
    public string $fecha               = '';
    public string $categoria           = 'novillo';
    public string $cantidad            = '';
    public string $peso_total_kg       = '';
    public string $precio_cabeza       = '';
    public string $importe_total       = '';
    public string $procedencia_destino = '';
    public string $observaciones       = '';

    protected array $messages = [
        'id_establecimiento.required' => 'Debe seleccionar un establecimiento.',
        'id_establecimiento.exists'   => 'El establecimiento seleccionado no existe.',
        'tipo.required'               => 'El tipo de movimiento es obligatorio.',
        'tipo.in'                     => 'El tipo de movimiento no es válido.',
        'fecha.required'              => 'La fecha es obligatoria.',
        'fecha.date'                  => 'La fecha no es válida.',
        'categoria.required'          => 'La categoría es obligatoria.',
        'categoria.in'                => 'La categoría no es válida.',
        'cantidad.required'           => 'La cantidad de cabezas es obligatoria.',
        'cantidad.integer'            => 'La cantidad debe ser un número entero.',
        'cantidad.min'                => 'La cantidad debe ser al menos 1.',
        'peso_total_kg.numeric'       => 'El peso debe ser un número.',
        'peso_total_kg.min'           => 'El peso no puede ser negativo.',
        'precio_cabeza.numeric'       => 'El precio por cabeza debe ser un número.',
        'precio_cabeza.min'           => 'El precio no puede ser negativo.',
        'importe_total.numeric'       => 'El importe total debe ser un número.',
        'importe_total.min'           => 'El importe no puede ser negativo.',
    ];

    protected function rules(): array
    {
        return [
            'id_establecimiento'  => 'required|exists:establecimientos,id',
            'tipo'                => 'required|in:' . implode(',', array_keys(Movimiento::TIPOS)),
            'fecha'               => 'required|date',
            'categoria'           => 'required|in:' . implode(',', array_keys(Animal::CATEGORIAS)),
            'cantidad'            => 'required|integer|min:1',
            'peso_total_kg'       => 'nullable|numeric|min:0',
            'precio_cabeza'       => 'nullable|numeric|min:0',
            'importe_total'       => 'nullable|numeric|min:0',
            'procedencia_destino' => 'nullable|string|max:150',
            'observaciones'       => 'nullable|string|max:2000',
        ];
    }

    public function updatingBusqueda(): void              { $this->resetPage(); }
    public function updatingFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatingFiltroTipo(): void            { $this->resetPage(); }
    public function updatingFiltroCategoria(): void       { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('ganaderia.movimientos.registrar');
        $this->limpiarFormulario();
        $this->fecha = now()->format('Y-m-d');
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('ganaderia.movimientos.registrar');

        $mov = Movimiento::findOrFail($id);

        $this->movimientoEditandoId  = $id;
        $this->modoEdicion           = true;
        $this->id_establecimiento    = $mov->id_establecimiento;
        $this->tipo                  = $mov->tipo;
        $this->fecha                 = $mov->fecha->format('Y-m-d');
        $this->categoria             = $mov->categoria;
        $this->cantidad              = (string) $mov->cantidad;
        $this->peso_total_kg         = $mov->peso_total_kg !== null ? (string) $mov->peso_total_kg : '';
        $this->precio_cabeza         = $mov->precio_cabeza !== null ? (string) $mov->precio_cabeza : '';
        $this->importe_total         = $mov->importe_total !== null ? (string) $mov->importe_total : '';
        $this->procedencia_destino   = $mov->procedencia_destino ?? '';
        $this->observaciones         = $mov->observaciones ?? '';

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('ganaderia.movimientos.registrar');

        $datos = $this->validate();

        foreach (['peso_total_kg', 'precio_cabeza', 'importe_total', 'procedencia_destino', 'observaciones'] as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] === '') $datos[$campo] = null;
        }

        if ($this->modoEdicion) {
            $mov = Movimiento::findOrFail($this->movimientoEditandoId);
            $mov->update($datos);
            session()->flash('success', "Movimiento actualizado correctamente.");
        } else {
            Movimiento::create($datos);
            session()->flash('success', "Movimiento de {$datos['cantidad']} cabezas registrado correctamente.");
        }

        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'movimientoEditandoId', 'modoEdicion',
            'id_establecimiento', 'fecha',
            'cantidad', 'peso_total_kg', 'precio_cabeza', 'importe_total',
            'procedencia_destino', 'observaciones',
        ]);
        $this->tipo      = 'compra';
        $this->categoria = 'novillo';
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $movimientos = Movimiento::query()
            ->with('establecimiento')
            ->when($this->busqueda, fn ($q) =>
                $q->where('procedencia_destino', 'like', "%{$this->busqueda}%")
            )
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroTipo,            fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroCategoria,       fn ($q) => $q->where('categoria', $this->filtroCategoria))
            ->orderByDesc('fecha')
            ->paginate(20);

        $establecimientos = Establecimiento::query()->activos()->orderBy('nombre')->get(['id', 'nombre']);

        return view('livewire.ganaderia.gestion-movimientos', [
            'movimientos'      => $movimientos,
            'establecimientos' => $establecimientos,
            'tiposMovimiento'  => Movimiento::TIPOS,
            'categorias'       => Animal::CATEGORIAS,
        ]);
    }
}
