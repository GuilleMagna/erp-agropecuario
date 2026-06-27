<?php

namespace App\Livewire\Ganaderia;

use App\Models\Animal;
use App\Models\Establecimiento;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionAnimales extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda              = '';
    public string $filtroEstablecimiento = '';
    public string $filtroCategoria       = '';
    public string $filtroRaza            = '';
    public string $filtroSexo            = '';
    public string $filtroActivo          = '1';

    // Estado modal
    public bool    $modalAbierto    = false;
    public bool    $modoEdicion     = false;
    public ?string $animalEditandoId = null;

    // Campos del formulario
    public string $id_establecimiento = '';
    public string $caravana           = '';
    public string $raza               = '';
    public string $sexo               = 'macho';
    public string $categoria          = 'novillo';
    public string $fecha_nacimiento   = '';
    public string $fecha_ingreso      = '';
    public string $peso_ingreso_kg    = '';
    public string $color              = '';
    public string $observaciones      = '';
    public bool   $activo             = true;

    protected array $messages = [
        'id_establecimiento.required' => 'Debe seleccionar un establecimiento.',
        'id_establecimiento.exists'   => 'El establecimiento seleccionado no existe.',
        'sexo.required'               => 'El sexo es obligatorio.',
        'sexo.in'                     => 'El sexo seleccionado no es válido.',
        'categoria.required'          => 'La categoría es obligatoria.',
        'categoria.in'                => 'La categoría seleccionada no es válida.',
        'fecha_ingreso.required'      => 'La fecha de ingreso es obligatoria.',
        'fecha_ingreso.date'          => 'La fecha de ingreso no es válida.',
        'fecha_nacimiento.date'       => 'La fecha de nacimiento no es válida.',
        'peso_ingreso_kg.numeric'     => 'El peso debe ser un número.',
        'peso_ingreso_kg.min'         => 'El peso no puede ser negativo.',
    ];

    protected function rules(): array
    {
        return [
            'id_establecimiento' => 'required|exists:establecimientos,id',
            'caravana'           => 'nullable|string|max:30',
            'raza'               => 'nullable|in:' . implode(',', array_keys(Animal::RAZAS)),
            'sexo'               => 'required|in:macho,hembra',
            'categoria'          => 'required|in:' . implode(',', array_keys(Animal::CATEGORIAS)),
            'fecha_nacimiento'   => 'nullable|date',
            'fecha_ingreso'      => 'required|date',
            'peso_ingreso_kg'    => 'nullable|numeric|min:0',
            'color'              => 'nullable|string|max:50',
            'observaciones'      => 'nullable|string|max:2000',
        ];
    }

    public function updatingBusqueda(): void              { $this->resetPage(); }
    public function updatingFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatingFiltroCategoria(): void       { $this->resetPage(); }
    public function updatingFiltroRaza(): void            { $this->resetPage(); }
    public function updatingFiltroSexo(): void            { $this->resetPage(); }
    public function updatingFiltroActivo(): void          { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('ganaderia.animales.crear');
        $this->limpiarFormulario();
        $this->fecha_ingreso = now()->format('Y-m-d');
        $this->modalAbierto  = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('ganaderia.animales.editar');

        $animal = Animal::findOrFail($id);

        $this->animalEditandoId   = $id;
        $this->modoEdicion        = true;
        $this->id_establecimiento = $animal->id_establecimiento;
        $this->caravana           = $animal->caravana ?? '';
        $this->raza               = $animal->raza ?? '';
        $this->sexo               = $animal->sexo;
        $this->categoria          = $animal->categoria;
        $this->fecha_nacimiento   = $animal->fecha_nacimiento?->format('Y-m-d') ?? '';
        $this->fecha_ingreso      = $animal->fecha_ingreso->format('Y-m-d');
        $this->peso_ingreso_kg    = $animal->peso_ingreso_kg !== null ? (string) $animal->peso_ingreso_kg : '';
        $this->color              = $animal->color ?? '';
        $this->observaciones      = $animal->observaciones ?? '';
        $this->activo             = $animal->activo;

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize($this->modoEdicion ? 'ganaderia.animales.editar' : 'ganaderia.animales.crear');

        $datos = $this->validate();

        foreach (['caravana', 'raza', 'fecha_nacimiento', 'peso_ingreso_kg', 'color', 'observaciones'] as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] === '') $datos[$campo] = null;
        }

        if ($this->modoEdicion) {
            $animal = Animal::findOrFail($this->animalEditandoId);
            $animal->update(array_merge($datos, ['activo' => $this->activo]));
            $label = $animal->caravana ?? $animal->categoria_label;
            session()->flash('success', "Animal {$label} actualizado correctamente.");
        } else {
            $datos['peso_actual_kg'] = $datos['peso_ingreso_kg'] ?? null;
            $animal = Animal::create($datos);
            $label  = $animal->caravana ?? $animal->categoria_label;
            session()->flash('success', "Animal {$label} registrado correctamente.");
        }

        $this->cerrarModal();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('ganaderia.animales.editar');

        $animal = Animal::findOrFail($id);
        $animal->update(['activo' => ! $animal->activo]);
        $label  = $animal->caravana ?? $animal->categoria_label;
        $estado = $animal->activo ? 'activado' : 'dado de baja';
        session()->flash('success', "Animal {$label} {$estado}.");
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'animalEditandoId', 'modoEdicion',
            'id_establecimiento', 'caravana', 'raza',
            'fecha_nacimiento', 'fecha_ingreso',
            'peso_ingreso_kg', 'color', 'observaciones',
        ]);
        $this->sexo      = 'macho';
        $this->categoria = 'novillo';
        $this->activo    = true;
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $animales = Animal::query()
            ->with('establecimiento')
            ->when($this->busqueda, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('caravana', 'like', "%{$this->busqueda}%")
                          ->orWhere('color', 'like', "%{$this->busqueda}%");
                });
            })
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroCategoria,       fn ($q) => $q->where('categoria', $this->filtroCategoria))
            ->when($this->filtroRaza,            fn ($q) => $q->where('raza', $this->filtroRaza))
            ->when($this->filtroSexo,            fn ($q) => $q->where('sexo', $this->filtroSexo))
            ->when($this->filtroActivo !== '',   fn ($q) => $q->where('activo', $this->filtroActivo === '1'))
            ->orderBy('categoria')
            ->orderBy('caravana')
            ->paginate(20);

        $establecimientos = Establecimiento::query()->activos()->orderBy('nombre')->get(['id', 'nombre']);

        return view('livewire.ganaderia.gestion-animales', [
            'animales'         => $animales,
            'establecimientos' => $establecimientos,
            'categorias'       => Animal::CATEGORIAS,
            'razas'            => Animal::RAZAS,
            'sexos'            => Animal::SEXOS,
        ]);
    }
}
