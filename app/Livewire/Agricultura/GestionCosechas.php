<?php

namespace App\Livewire\Agricultura;

use App\Models\Campana;
use App\Models\Cosecha;
use App\Models\Siembra;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCosechas extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda      = '';
    public string $filtroCampana = '';
    public string $filtroSiembra = '';

    // Estado del modal
    public bool    $modalAbierto      = false;
    public bool    $modoEdicion       = false;
    public ?string $cosechaEditandoId = null;

    // Campos del formulario
    public string $id_siembra             = '';
    public string $fecha_cosecha          = '';
    public string $superficie_cosechada_ha = '';
    public string $rinde_kg_ha            = '';
    public string $humedad_porc           = '';
    public string $observaciones          = '';

    protected array $messages = [
        'id_siembra.required'                   => 'Debe seleccionar una siembra.',
        'id_siembra.exists'                      => 'La siembra seleccionada no existe.',
        'fecha_cosecha.required'                 => 'La fecha de cosecha es obligatoria.',
        'fecha_cosecha.date'                     => 'La fecha de cosecha no es válida.',
        'superficie_cosechada_ha.required'       => 'La superficie cosechada es obligatoria.',
        'superficie_cosechada_ha.numeric'        => 'La superficie debe ser un número.',
        'superficie_cosechada_ha.min'            => 'La superficie no puede ser negativa.',
        'rinde_kg_ha.required'                   => 'El rinde es obligatorio.',
        'rinde_kg_ha.numeric'                    => 'El rinde debe ser un número.',
        'rinde_kg_ha.min'                        => 'El rinde no puede ser negativo.',
        'humedad_porc.numeric'                   => 'La humedad debe ser un número.',
        'humedad_porc.between'                   => 'La humedad debe estar entre 0 y 100.',
    ];

    protected function rules(): array
    {
        return [
            'id_siembra'             => 'required|exists:siembras,id',
            'fecha_cosecha'          => 'required|date',
            'superficie_cosechada_ha'=> 'required|numeric|min:0',
            'rinde_kg_ha'            => 'required|numeric|min:0',
            'humedad_porc'           => 'nullable|numeric|between:0,100',
            'observaciones'          => 'nullable|string|max:2000',
        ];
    }

    public function updatingBusqueda(): void      { $this->resetPage(); }
    public function updatingFiltroCampana(): void { $this->resetPage(); }
    public function updatingFiltroSiembra(): void { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('agricultura.cosecha.registrar');
        $this->limpiarFormulario();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('agricultura.cosecha.registrar');

        $cosecha = Cosecha::findOrFail($id);

        $this->cosechaEditandoId       = $id;
        $this->modoEdicion             = true;
        $this->id_siembra              = $cosecha->id_siembra;
        $this->fecha_cosecha           = $cosecha->fecha_cosecha->format('Y-m-d');
        $this->superficie_cosechada_ha = (string) $cosecha->superficie_cosechada_ha;
        $this->rinde_kg_ha             = (string) $cosecha->rinde_kg_ha;
        $this->humedad_porc            = $cosecha->humedad_porc !== null ? (string) $cosecha->humedad_porc : '';
        $this->observaciones           = $cosecha->observaciones ?? '';

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('agricultura.cosecha.registrar');

        $datos = $this->validate();

        if (isset($datos['humedad_porc']) && $datos['humedad_porc'] === '') {
            $datos['humedad_porc'] = null;
        }
        if (isset($datos['observaciones']) && $datos['observaciones'] === '') {
            $datos['observaciones'] = null;
        }

        // Producción total almacenada para performance en reportes
        $datos['produccion_total_kg'] = round(
            (float) $datos['superficie_cosechada_ha'] * (float) $datos['rinde_kg_ha'],
            2
        );

        if ($this->modoEdicion) {
            $cosecha = Cosecha::findOrFail($this->cosechaEditandoId);
            $cosecha->update($datos);
            session()->flash('success', 'Cosecha actualizada correctamente.');
        } else {
            Cosecha::create($datos);
            session()->flash('success', 'Cosecha registrada correctamente.');
        }

        // Marcar siembra como cosechada si no lo estaba
        $siembra = Siembra::find($datos['id_siembra']);
        if ($siembra && ! in_array($siembra->estado, ['cosechada', 'perdida'])) {
            $siembra->update(['estado' => 'cosechada']);
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
            'cosechaEditandoId', 'modoEdicion',
            'id_siembra', 'fecha_cosecha',
            'superficie_cosechada_ha', 'rinde_kg_ha', 'humedad_porc', 'observaciones',
        ]);
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $cosechas = Cosecha::query()
            ->with(['siembra.campana', 'siembra.lote'])
            ->when($this->busqueda, function ($q) {
                $q->whereHas('siembra', fn ($s) =>
                    $s->where('cultivo', 'like', "%{$this->busqueda}%")
                      ->orWhere('variedad', 'like', "%{$this->busqueda}%")
                );
            })
            ->when($this->filtroCampana, fn ($q) =>
                $q->whereHas('siembra', fn ($s) => $s->where('id_campana', $this->filtroCampana))
            )
            ->when($this->filtroSiembra, fn ($q) => $q->where('id_siembra', $this->filtroSiembra))
            ->orderByDesc('fecha_cosecha')
            ->paginate(15);

        $campanas        = Campana::query()->orderByDesc('nombre')->get(['id', 'nombre']);
        $siembrasOpciones = Siembra::query()
            ->with(['campana', 'lote'])
            ->orderByDesc('fecha_siembra')
            ->get(['id', 'id_campana', 'id_lote', 'cultivo', 'variedad', 'fecha_siembra']);

        return view('livewire.agricultura.gestion-cosechas', [
            'cosechas'        => $cosechas,
            'campanas'        => $campanas,
            'siembrasOpciones'=> $siembrasOpciones,
            'cultivos'        => Siembra::CULTIVOS,
        ]);
    }
}
