<?php

namespace App\Livewire\Ganaderia;

use App\Models\Animal;
use App\Models\Establecimiento;
use App\Models\Pesaje;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionPesajes extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda              = '';
    public string $filtroEstablecimiento = '';
    public string $filtroCategoria       = '';
    public string $modoRegistro          = '';  // 'individual' | 'grupal'

    // Estado modal
    public bool    $modalAbierto      = false;
    public bool    $modoEdicion       = false;
    public ?string $pesajeEditandoId  = null;

    // Campos del formulario
    public string $id_establecimiento = '';
    public string $id_animal          = '';
    public string $fecha              = '';
    public string $categoria          = '';
    public string $cantidad           = '1';
    public string $peso_kg            = '';
    public string $observaciones      = '';
    public bool   $esIndividual       = true;

    protected array $messages = [
        'id_establecimiento.required' => 'Debe seleccionar un establecimiento.',
        'id_establecimiento.exists'   => 'El establecimiento seleccionado no existe.',
        'id_animal.exists'            => 'El animal seleccionado no existe.',
        'fecha.required'              => 'La fecha es obligatoria.',
        'fecha.date'                  => 'La fecha no es válida.',
        'peso_kg.required'            => 'El peso es obligatorio.',
        'peso_kg.numeric'             => 'El peso debe ser un número.',
        'peso_kg.min'                 => 'El peso no puede ser negativo.',
        'cantidad.required'           => 'La cantidad de cabezas es obligatoria.',
        'cantidad.integer'            => 'La cantidad debe ser un número entero.',
        'cantidad.min'                => 'La cantidad debe ser al menos 1.',
        'categoria.required_if'       => 'La categoría es obligatoria para pesaje grupal.',
    ];

    protected function rules(): array
    {
        return [
            'id_establecimiento' => 'required|exists:establecimientos,id',
            'id_animal'          => 'nullable|exists:animales,id',
            'fecha'              => 'required|date',
            'categoria'          => 'nullable|in:' . implode(',', array_keys(Animal::CATEGORIAS)),
            'cantidad'           => 'required|integer|min:1',
            'peso_kg'            => 'required|numeric|min:0',
            'observaciones'      => 'nullable|string|max:2000',
        ];
    }

    public function updatingBusqueda(): void              { $this->resetPage(); }
    public function updatingFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatingFiltroCategoria(): void       { $this->resetPage(); }
    public function updatingModoRegistro(): void          { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('ganaderia.pesajes.registrar');
        $this->limpiarFormulario();
        $this->fecha = now()->format('Y-m-d');
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('ganaderia.pesajes.registrar');

        $pesaje = Pesaje::findOrFail($id);

        $this->pesajeEditandoId   = $id;
        $this->modoEdicion        = true;
        $this->id_establecimiento = $pesaje->id_establecimiento;
        $this->id_animal          = $pesaje->id_animal ?? '';
        $this->fecha              = $pesaje->fecha->format('Y-m-d');
        $this->categoria          = $pesaje->categoria ?? '';
        $this->cantidad           = (string) $pesaje->cantidad;
        $this->peso_kg            = (string) $pesaje->peso_kg;
        $this->observaciones      = $pesaje->observaciones ?? '';
        $this->esIndividual       = $pesaje->id_animal !== null;

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('ganaderia.pesajes.registrar');

        $datos = $this->validate();

        foreach (['id_animal', 'categoria', 'observaciones'] as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] === '') $datos[$campo] = null;
        }

        if ($this->modoEdicion) {
            $pesaje = Pesaje::findOrFail($this->pesajeEditandoId);
            $pesaje->update($datos);
            session()->flash('success', 'Pesaje actualizado correctamente.');
        } else {
            $pesaje = Pesaje::create($datos);
            session()->flash('success', "Pesaje de {$datos['peso_kg']} kg registrado correctamente.");
        }

        // Actualizar peso_actual_kg del animal si es pesaje individual
        if ($pesaje->id_animal) {
            Animal::where('id', $pesaje->id_animal)->update(['peso_actual_kg' => $pesaje->peso_kg]);
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
            'pesajeEditandoId', 'modoEdicion',
            'id_establecimiento', 'id_animal', 'fecha',
            'categoria', 'peso_kg', 'observaciones',
        ]);
        $this->cantidad    = '1';
        $this->esIndividual = true;
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $pesajes = Pesaje::query()
            ->with(['establecimiento', 'animal'])
            ->when($this->busqueda, function ($q) {
                $q->whereHas('animal', fn ($a) =>
                    $a->where('caravana', 'like', "%{$this->busqueda}%")
                );
            })
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroCategoria, fn ($q) =>
                $q->where(fn ($inner) =>
                    $inner->where('categoria', $this->filtroCategoria)
                          ->orWhereHas('animal', fn ($a) => $a->where('categoria', $this->filtroCategoria))
                )
            )
            ->when($this->modoRegistro === 'individual', fn ($q) => $q->whereNotNull('id_animal'))
            ->when($this->modoRegistro === 'grupal',     fn ($q) => $q->whereNull('id_animal'))
            ->orderByDesc('fecha')
            ->paginate(20);

        $establecimientos = Establecimiento::query()->activos()->orderBy('nombre')->get(['id', 'nombre']);
        $animalesOpciones = Animal::query()
            ->activos()
            ->when($this->id_establecimiento, fn ($q) => $q->where('id_establecimiento', $this->id_establecimiento))
            ->orderBy('categoria')
            ->orderBy('caravana')
            ->get(['id', 'caravana', 'categoria', 'raza']);

        return view('livewire.ganaderia.gestion-pesajes', [
            'pesajes'          => $pesajes,
            'establecimientos' => $establecimientos,
            'animalesOpciones' => $animalesOpciones,
            'categorias'       => Animal::CATEGORIAS,
        ]);
    }
}
