<?php

namespace App\Livewire\Ganaderia;

use App\Models\Animal;
use App\Models\Establecimiento;
use App\Models\EventoSanidad;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionSanidad extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda              = '';
    public string $filtroEstablecimiento = '';
    public string $filtroTipoEvento      = '';

    // Estado modal
    public bool    $modalAbierto        = false;
    public bool    $modoEdicion         = false;
    public ?string $eventoEditandoId    = null;

    // Campos del formulario
    public string $id_establecimiento  = '';
    public string $id_animal           = '';
    public string $tipo_evento         = 'vacunacion';
    public string $fecha               = '';
    public string $producto            = '';
    public string $dosis               = '';
    public string $veterinario         = '';
    public string $categoria_afectada  = '';
    public string $cantidad_afectada   = '';
    public string $observaciones       = '';

    protected array $messages = [
        'id_establecimiento.required' => 'Debe seleccionar un establecimiento.',
        'id_establecimiento.exists'   => 'El establecimiento seleccionado no existe.',
        'id_animal.exists'            => 'El animal seleccionado no existe.',
        'tipo_evento.required'        => 'El tipo de evento es obligatorio.',
        'tipo_evento.in'              => 'El tipo de evento no es válido.',
        'fecha.required'              => 'La fecha del evento es obligatoria.',
        'fecha.date'                  => 'La fecha no es válida.',
        'cantidad_afectada.integer'   => 'La cantidad debe ser un número entero.',
        'cantidad_afectada.min'       => 'La cantidad debe ser al menos 1.',
    ];

    protected function rules(): array
    {
        return [
            'id_establecimiento' => 'required|exists:establecimientos,id',
            'id_animal'          => 'nullable|exists:animales,id',
            'tipo_evento'        => 'required|in:' . implode(',', array_keys(EventoSanidad::TIPOS)),
            'fecha'              => 'required|date',
            'producto'           => 'nullable|string|max:100',
            'dosis'              => 'nullable|string|max:50',
            'veterinario'        => 'nullable|string|max:100',
            'categoria_afectada' => 'nullable|in:' . implode(',', array_keys(Animal::CATEGORIAS)),
            'cantidad_afectada'  => 'nullable|integer|min:1',
            'observaciones'      => 'nullable|string|max:2000',
        ];
    }

    public function updatingBusqueda(): void              { $this->resetPage(); }
    public function updatingFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatingFiltroTipoEvento(): void      { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('ganaderia.sanidad.registrar');
        $this->limpiarFormulario();
        $this->fecha = now()->format('Y-m-d');
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('ganaderia.sanidad.registrar');

        $evento = EventoSanidad::findOrFail($id);

        $this->eventoEditandoId   = $id;
        $this->modoEdicion        = true;
        $this->id_establecimiento = $evento->id_establecimiento;
        $this->id_animal          = $evento->id_animal ?? '';
        $this->tipo_evento        = $evento->tipo_evento;
        $this->fecha              = $evento->fecha->format('Y-m-d');
        $this->producto           = $evento->producto ?? '';
        $this->dosis              = $evento->dosis ?? '';
        $this->veterinario        = $evento->veterinario ?? '';
        $this->categoria_afectada = $evento->categoria_afectada ?? '';
        $this->cantidad_afectada  = $evento->cantidad_afectada !== null ? (string) $evento->cantidad_afectada : '';
        $this->observaciones      = $evento->observaciones ?? '';

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('ganaderia.sanidad.registrar');

        $datos = $this->validate();

        foreach (['id_animal', 'producto', 'dosis', 'veterinario', 'categoria_afectada', 'cantidad_afectada', 'observaciones'] as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] === '') $datos[$campo] = null;
        }

        if ($this->modoEdicion) {
            $evento = EventoSanidad::findOrFail($this->eventoEditandoId);
            $evento->update($datos);
            session()->flash('success', 'Evento sanitario actualizado correctamente.');
        } else {
            EventoSanidad::create($datos);
            $label = EventoSanidad::TIPOS[$datos['tipo_evento']] ?? $datos['tipo_evento'];
            session()->flash('success', "{$label} registrada correctamente.");
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
            'eventoEditandoId', 'modoEdicion',
            'id_establecimiento', 'id_animal', 'fecha',
            'producto', 'dosis', 'veterinario',
            'categoria_afectada', 'cantidad_afectada', 'observaciones',
        ]);
        $this->tipo_evento = 'vacunacion';
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $eventos = EventoSanidad::query()
            ->with(['establecimiento', 'animal'])
            ->when($this->busqueda, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('producto', 'like', "%{$this->busqueda}%")
                          ->orWhere('veterinario', 'like', "%{$this->busqueda}%");
                });
            })
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroTipoEvento,      fn ($q) => $q->where('tipo_evento', $this->filtroTipoEvento))
            ->orderByDesc('fecha')
            ->paginate(20);

        $establecimientos = Establecimiento::query()->activos()->orderBy('nombre')->get(['id', 'nombre']);
        $animalesOpciones = Animal::query()
            ->activos()
            ->when($this->id_establecimiento, fn ($q) => $q->where('id_establecimiento', $this->id_establecimiento))
            ->orderBy('categoria')
            ->orderBy('caravana')
            ->get(['id', 'caravana', 'categoria']);

        return view('livewire.ganaderia.gestion-sanidad', [
            'eventos'          => $eventos,
            'establecimientos' => $establecimientos,
            'animalesOpciones' => $animalesOpciones,
            'tiposEvento'      => EventoSanidad::TIPOS,
            'categorias'       => Animal::CATEGORIAS,
        ]);
    }
}
