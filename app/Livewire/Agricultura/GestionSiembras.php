<?php

namespace App\Livewire\Agricultura;

use App\Models\Campana;
use App\Models\Lote;
use App\Models\Siembra;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionSiembras extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda       = '';
    public string $filtroCampana  = '';
    public string $filtroLote     = '';
    public string $filtroCultivo  = '';
    public string $filtroEstado   = '';

    // Estado del modal
    public bool    $modalAbierto      = false;
    public bool    $modoEdicion       = false;
    public ?string $siembraEditandoId = null;

    // Campos del formulario
    public string $id_campana            = '';
    public string $id_lote               = '';
    public string $cultivo               = '';
    public string $variedad              = '';
    public string $fecha_siembra         = '';
    public string $superficie_sembrada_ha = '';
    public string $densidad_siembra      = '';
    public string $estado                = 'sembrada';
    public string $observaciones         = '';

    protected array $messages = [
        'id_campana.required'              => 'Debe seleccionar una campaña.',
        'id_campana.exists'                => 'La campaña seleccionada no existe.',
        'id_lote.required'                 => 'Debe seleccionar un lote.',
        'id_lote.exists'                   => 'El lote seleccionado no existe.',
        'cultivo.required'                 => 'El cultivo es obligatorio.',
        'cultivo.in'                       => 'El cultivo seleccionado no es válido.',
        'fecha_siembra.required'           => 'La fecha de siembra es obligatoria.',
        'fecha_siembra.date'               => 'La fecha de siembra no es válida.',
        'superficie_sembrada_ha.required'  => 'La superficie sembrada es obligatoria.',
        'superficie_sembrada_ha.numeric'   => 'La superficie debe ser un número.',
        'superficie_sembrada_ha.min'       => 'La superficie no puede ser negativa.',
        'densidad_siembra.numeric'         => 'La densidad debe ser un número.',
        'densidad_siembra.min'             => 'La densidad no puede ser negativa.',
        'estado.in'                        => 'El estado seleccionado no es válido.',
    ];

    protected function rules(): array
    {
        return [
            'id_campana'            => 'required|exists:campanas,id',
            'id_lote'               => 'required|exists:lotes,id',
            'cultivo'               => 'required|in:' . implode(',', array_keys(Siembra::CULTIVOS)),
            'variedad'              => 'nullable|string|max:100',
            'fecha_siembra'         => 'required|date',
            'superficie_sembrada_ha'=> 'required|numeric|min:0',
            'densidad_siembra'      => 'nullable|numeric|min:0',
            'estado'                => 'required|in:' . implode(',', array_keys(Siembra::ESTADOS)),
            'observaciones'         => 'nullable|string|max:2000',
        ];
    }

    public function updatingBusqueda(): void      { $this->resetPage(); }
    public function updatingFiltroCampana(): void { $this->resetPage(); }
    public function updatingFiltroLote(): void    { $this->resetPage(); }
    public function updatingFiltroCultivo(): void { $this->resetPage(); }
    public function updatingFiltroEstado(): void  { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('agricultura.siembra.crear');
        $this->limpiarFormulario();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('agricultura.siembra.editar');

        $siembra = Siembra::findOrFail($id);

        $this->siembraEditandoId       = $id;
        $this->modoEdicion             = true;
        $this->id_campana              = $siembra->id_campana;
        $this->id_lote                 = $siembra->id_lote;
        $this->cultivo                 = $siembra->cultivo;
        $this->variedad                = $siembra->variedad ?? '';
        $this->fecha_siembra           = $siembra->fecha_siembra->format('Y-m-d');
        $this->superficie_sembrada_ha  = (string) $siembra->superficie_sembrada_ha;
        $this->densidad_siembra        = $siembra->densidad_siembra !== null ? (string) $siembra->densidad_siembra : '';
        $this->estado                  = $siembra->estado;
        $this->observaciones           = $siembra->observaciones ?? '';

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize($this->modoEdicion ? 'agricultura.siembra.editar' : 'agricultura.siembra.crear');

        $datos = $this->validate();

        foreach (['variedad', 'densidad_siembra', 'observaciones'] as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] === '') $datos[$campo] = null;
        }

        if ($this->modoEdicion) {
            $siembra = Siembra::findOrFail($this->siembraEditandoId);
            $siembra->update($datos);
            session()->flash('success', "Siembra de {$siembra->cultivo_label} actualizada correctamente.");
        } else {
            $siembra = Siembra::create($datos);
            session()->flash('success', "Siembra de {$siembra->cultivo_label} registrada correctamente.");
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
            'siembraEditandoId', 'modoEdicion',
            'id_campana', 'id_lote', 'cultivo', 'variedad',
            'fecha_siembra', 'superficie_sembrada_ha', 'densidad_siembra', 'observaciones',
        ]);
        $this->estado = 'sembrada';
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $siembras = Siembra::query()
            ->with(['campana', 'lote'])
            ->when($this->busqueda, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('cultivo', 'like', "%{$this->busqueda}%")
                          ->orWhere('variedad', 'like', "%{$this->busqueda}%");
                });
            })
            ->when($this->filtroCampana, fn ($q) => $q->where('id_campana', $this->filtroCampana))
            ->when($this->filtroLote,    fn ($q) => $q->where('id_lote', $this->filtroLote))
            ->when($this->filtroCultivo, fn ($q) => $q->where('cultivo', $this->filtroCultivo))
            ->when($this->filtroEstado,  fn ($q) => $q->where('estado', $this->filtroEstado))
            ->orderByDesc('fecha_siembra')
            ->paginate(15);

        $campanas = Campana::query()->orderByDesc('nombre')->get(['id', 'nombre']);
        $lotes    = Lote::query()->activos()->with('establecimiento')->orderBy('nombre')->get(['id', 'nombre', 'id_establecimiento']);

        return view('livewire.agricultura.gestion-siembras', [
            'siembras' => $siembras,
            'campanas' => $campanas,
            'lotes'    => $lotes,
            'cultivos' => Siembra::CULTIVOS,
            'estados'  => Siembra::ESTADOS,
        ]);
    }
}
