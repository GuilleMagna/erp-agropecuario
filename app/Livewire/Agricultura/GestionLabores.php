<?php

namespace App\Livewire\Agricultura;

use App\Models\Campana;
use App\Models\Labor;
use App\Models\Lote;
use App\Models\Siembra;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionLabores extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda          = '';
    public string $filtroCampana     = '';
    public string $filtroLote        = '';
    public string $filtroTipoLabor   = '';

    // Estado del modal
    public bool    $modalAbierto    = false;
    public bool    $modoEdicion     = false;
    public ?string $laborEditandoId = null;

    // Campos del formulario
    public string $id_campana             = '';
    public string $id_lote                = '';
    public string $id_siembra             = '';
    public string $tipo_labor             = '';
    public string $fecha                  = '';
    public string $descripcion            = '';
    public string $superficie_trabajada_ha = '';
    public string $observaciones          = '';

    protected array $messages = [
        'id_campana.required'   => 'Debe seleccionar una campaña.',
        'id_campana.exists'     => 'La campaña seleccionada no existe.',
        'id_lote.required'      => 'Debe seleccionar un lote.',
        'id_lote.exists'        => 'El lote seleccionado no existe.',
        'id_siembra.exists'     => 'La siembra seleccionada no existe.',
        'tipo_labor.required'   => 'El tipo de labor es obligatorio.',
        'tipo_labor.in'         => 'El tipo de labor seleccionado no es válido.',
        'fecha.required'        => 'La fecha es obligatoria.',
        'fecha.date'            => 'La fecha no es válida.',
        'superficie_trabajada_ha.numeric' => 'La superficie debe ser un número.',
        'superficie_trabajada_ha.min'     => 'La superficie no puede ser negativa.',
    ];

    protected function rules(): array
    {
        return [
            'id_campana'              => 'required|exists:campanas,id',
            'id_lote'                 => 'required|exists:lotes,id',
            'id_siembra'              => 'nullable|exists:siembras,id',
            'tipo_labor'              => 'required|in:' . implode(',', array_keys(Labor::TIPOS)),
            'fecha'                   => 'required|date',
            'descripcion'             => 'nullable|string|max:200',
            'superficie_trabajada_ha' => 'nullable|numeric|min:0',
            'observaciones'           => 'nullable|string|max:2000',
        ];
    }

    public function updatingBusqueda(): void        { $this->resetPage(); }
    public function updatingFiltroCampana(): void   { $this->resetPage(); }
    public function updatingFiltroLote(): void      { $this->resetPage(); }
    public function updatingFiltroTipoLabor(): void { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('agricultura.labores.crear');
        $this->limpiarFormulario();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('agricultura.labores.editar');

        $labor = Labor::findOrFail($id);

        $this->laborEditandoId         = $id;
        $this->modoEdicion             = true;
        $this->id_campana              = $labor->id_campana;
        $this->id_lote                 = $labor->id_lote;
        $this->id_siembra              = $labor->id_siembra ?? '';
        $this->tipo_labor              = $labor->tipo_labor;
        $this->fecha                   = $labor->fecha->format('Y-m-d');
        $this->descripcion             = $labor->descripcion ?? '';
        $this->superficie_trabajada_ha = $labor->superficie_trabajada_ha !== null
            ? (string) $labor->superficie_trabajada_ha : '';
        $this->observaciones           = $labor->observaciones ?? '';

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize($this->modoEdicion ? 'agricultura.labores.editar' : 'agricultura.labores.crear');

        $datos = $this->validate();

        foreach (['id_siembra', 'descripcion', 'superficie_trabajada_ha', 'observaciones'] as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] === '') $datos[$campo] = null;
        }

        if ($this->modoEdicion) {
            $labor = Labor::findOrFail($this->laborEditandoId);
            $labor->update($datos);
            session()->flash('success', "Labor de {$labor->tipo_labor_label} actualizada correctamente.");
        } else {
            $labor = Labor::create($datos);
            session()->flash('success', "Labor de {$labor->tipo_labor_label} registrada correctamente.");
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
            'laborEditandoId', 'modoEdicion',
            'id_campana', 'id_lote', 'id_siembra',
            'tipo_labor', 'fecha', 'descripcion',
            'superficie_trabajada_ha', 'observaciones',
        ]);
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $labores = Labor::query()
            ->with(['campana', 'lote', 'siembra'])
            ->when($this->busqueda, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('tipo_labor', 'like', "%{$this->busqueda}%")
                          ->orWhere('descripcion', 'like', "%{$this->busqueda}%");
                });
            })
            ->when($this->filtroCampana,   fn ($q) => $q->where('id_campana', $this->filtroCampana))
            ->when($this->filtroLote,      fn ($q) => $q->where('id_lote', $this->filtroLote))
            ->when($this->filtroTipoLabor, fn ($q) => $q->where('tipo_labor', $this->filtroTipoLabor))
            ->orderByDesc('fecha')
            ->paginate(15);

        $campanas        = Campana::query()->orderByDesc('nombre')->get(['id', 'nombre']);
        $lotes           = Lote::query()->activos()->orderBy('nombre')->get(['id', 'nombre']);
        $siembrasOpciones = Siembra::query()
            ->with(['campana', 'lote'])
            ->orderByDesc('fecha_siembra')
            ->get(['id', 'id_campana', 'id_lote', 'cultivo', 'fecha_siembra']);

        return view('livewire.agricultura.gestion-labores', [
            'labores'          => $labores,
            'campanas'         => $campanas,
            'lotes'            => $lotes,
            'siembrasOpciones' => $siembrasOpciones,
            'tiposLabor'       => Labor::TIPOS,
        ]);
    }
}
