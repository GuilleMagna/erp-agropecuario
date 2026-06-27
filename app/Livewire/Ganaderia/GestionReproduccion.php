<?php

namespace App\Livewire\Ganaderia;

use App\Models\Animal;
use App\Models\Establecimiento;
use App\Models\EventoReproduccion;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionReproduccion extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda              = '';
    public string $filtroEstablecimiento = '';
    public string $filtroTipoEvento      = '';
    public string $filtroResultado       = '';

    // Estado modal
    public bool    $modalAbierto        = false;
    public bool    $modoEdicion         = false;
    public ?string $eventoEditandoId    = null;

    // Campos del formulario
    public string $id_establecimiento = '';
    public string $id_animal          = '';
    public string $tipo_evento        = 'servicio';
    public string $fecha              = '';
    public string $resultado          = '';
    public string $toro_caravana      = '';
    public string $observaciones      = '';

    protected array $messages = [
        'id_establecimiento.required' => 'Debe seleccionar un establecimiento.',
        'id_establecimiento.exists'   => 'El establecimiento seleccionado no existe.',
        'id_animal.exists'            => 'El animal seleccionado no existe.',
        'tipo_evento.required'        => 'El tipo de evento es obligatorio.',
        'tipo_evento.in'              => 'El tipo de evento no es válido.',
        'fecha.required'              => 'La fecha del evento es obligatoria.',
        'fecha.date'                  => 'La fecha no es válida.',
        'resultado.in'                => 'El resultado seleccionado no es válido.',
    ];

    protected function rules(): array
    {
        return [
            'id_establecimiento' => 'required|exists:establecimientos,id',
            'id_animal'          => 'nullable|exists:animales,id',
            'tipo_evento'        => 'required|in:' . implode(',', array_keys(EventoReproduccion::TIPOS)),
            'fecha'              => 'required|date',
            'resultado'          => 'nullable|in:' . implode(',', array_keys(EventoReproduccion::RESULTADOS)),
            'toro_caravana'      => 'nullable|string|max:30',
            'observaciones'      => 'nullable|string|max:2000',
        ];
    }

    public function updatingBusqueda(): void              { $this->resetPage(); }
    public function updatingFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatingFiltroTipoEvento(): void      { $this->resetPage(); }
    public function updatingFiltroResultado(): void       { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('ganaderia.reproduccion.registrar');
        $this->limpiarFormulario();
        $this->fecha = now()->format('Y-m-d');
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('ganaderia.reproduccion.registrar');

        $evento = EventoReproduccion::findOrFail($id);

        $this->eventoEditandoId   = $id;
        $this->modoEdicion        = true;
        $this->id_establecimiento = $evento->id_establecimiento;
        $this->id_animal          = $evento->id_animal ?? '';
        $this->tipo_evento        = $evento->tipo_evento;
        $this->fecha              = $evento->fecha->format('Y-m-d');
        $this->resultado          = $evento->resultado ?? '';
        $this->toro_caravana      = $evento->toro_caravana ?? '';
        $this->observaciones      = $evento->observaciones ?? '';

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('ganaderia.reproduccion.registrar');

        $datos = $this->validate();

        foreach (['id_animal', 'resultado', 'toro_caravana', 'observaciones'] as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] === '') $datos[$campo] = null;
        }

        if ($this->modoEdicion) {
            $evento = EventoReproduccion::findOrFail($this->eventoEditandoId);
            $evento->update($datos);
            session()->flash('success', 'Evento reproductivo actualizado correctamente.');
        } else {
            EventoReproduccion::create($datos);
            $label = EventoReproduccion::TIPOS[$datos['tipo_evento']] ?? $datos['tipo_evento'];
            session()->flash('success', "{$label} registrado correctamente.");
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
            'resultado', 'toro_caravana', 'observaciones',
        ]);
        $this->tipo_evento = 'servicio';
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $eventos = EventoReproduccion::query()
            ->with(['establecimiento', 'animal'])
            ->when($this->busqueda, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('toro_caravana', 'like', "%{$this->busqueda}%")
                          ->orWhereHas('animal', fn ($a) =>
                              $a->where('caravana', 'like', "%{$this->busqueda}%")
                          );
                });
            })
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroTipoEvento,      fn ($q) => $q->where('tipo_evento', $this->filtroTipoEvento))
            ->when($this->filtroResultado,       fn ($q) => $q->where('resultado', $this->filtroResultado))
            ->orderByDesc('fecha')
            ->paginate(20);

        $establecimientos = Establecimiento::query()->activos()->orderBy('nombre')->get(['id', 'nombre']);
        $hembrasOpciones  = Animal::query()
            ->activos()
            ->where('sexo', 'hembra')
            ->when($this->id_establecimiento, fn ($q) => $q->where('id_establecimiento', $this->id_establecimiento))
            ->orderBy('categoria')
            ->orderBy('caravana')
            ->get(['id', 'caravana', 'categoria']);

        return view('livewire.ganaderia.gestion-reproduccion', [
            'eventos'          => $eventos,
            'establecimientos' => $establecimientos,
            'hembrasOpciones'  => $hembrasOpciones,
            'tiposEvento'      => EventoReproduccion::TIPOS,
            'resultados'       => EventoReproduccion::RESULTADOS,
            'categorias'       => Animal::CATEGORIAS,
        ]);
    }
}
