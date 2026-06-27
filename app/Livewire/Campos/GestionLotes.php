<?php

namespace App\Livewire\Campos;

use App\Models\Establecimiento;
use App\Models\Lote;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionLotes extends Component
{
    use WithPagination;

    const TIPOS = [
        'agricola'  => 'Agrícola',
        'ganadero'  => 'Ganadero',
        'mixto'     => 'Mixto',
        'forestal'  => 'Forestal',
        'sin_uso'   => 'Sin uso',
    ];

    // Filtros
    public string $busqueda              = '';
    public string $filtroEstablecimiento = '';
    public string $filtroTipo            = '';
    public string $filtroActivo          = '';

    // Estado del modal
    public bool    $modalAbierto    = false;
    public bool    $modoEdicion     = false;
    public ?string $loteEditandoId  = null;

    // Campos del formulario
    public string $nombre              = '';
    public string $codigo              = '';
    public string $id_establecimiento  = '';
    public string $tipo                = 'agricola';
    public string $superficie_ha       = '';
    public string $latitud             = '';
    public string $longitud            = '';
    public string $descripcion         = '';
    public bool   $activo              = true;

    protected array $messages = [
        'nombre.required'               => 'El nombre del lote es obligatorio.',
        'nombre.max'                    => 'El nombre no puede superar 100 caracteres.',
        'codigo.max'                    => 'El código no puede superar 30 caracteres.',
        'id_establecimiento.required'   => 'Debe seleccionar un establecimiento.',
        'id_establecimiento.exists'     => 'El establecimiento seleccionado no existe.',
        'tipo.in'                       => 'El tipo de lote seleccionado no es válido.',
        'superficie_ha.numeric'         => 'La superficie debe ser un número.',
        'superficie_ha.min'             => 'La superficie no puede ser negativa.',
        'latitud.numeric'               => 'La latitud debe ser un número.',
        'latitud.between'               => 'La latitud debe estar entre -90 y 90.',
        'longitud.numeric'              => 'La longitud debe ser un número.',
        'longitud.between'              => 'La longitud debe estar entre -180 y 180.',
    ];

    protected function rules(): array
    {
        return [
            'nombre'             => 'required|string|max:100',
            'codigo'             => 'nullable|string|max:30',
            'id_establecimiento' => 'required|exists:establecimientos,id',
            'tipo'               => 'required|in:agricola,ganadero,mixto,forestal,sin_uso',
            'superficie_ha'      => 'nullable|numeric|min:0',
            'latitud'            => 'nullable|numeric|between:-90,90',
            'longitud'           => 'nullable|numeric|between:-180,180',
            'descripcion'        => 'nullable|string|max:1000',
        ];
    }

    public function updatingBusqueda(): void              { $this->resetPage(); }
    public function updatingFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatingFiltroTipo(): void            { $this->resetPage(); }
    public function updatingFiltroActivo(): void          { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('campos.lotes.crear');
        $this->limpiarFormulario();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('campos.lotes.editar');

        $lote = Lote::findOrFail($id);

        $this->loteEditandoId    = $id;
        $this->modoEdicion       = true;
        $this->nombre            = $lote->nombre;
        $this->codigo            = $lote->codigo ?? '';
        $this->id_establecimiento = $lote->id_establecimiento;
        $this->tipo              = $lote->tipo;
        $this->superficie_ha     = $lote->superficie_ha !== null ? (string) $lote->superficie_ha : '';
        $this->latitud           = $lote->latitud !== null ? (string) $lote->latitud : '';
        $this->longitud          = $lote->longitud !== null ? (string) $lote->longitud : '';
        $this->descripcion       = $lote->descripcion ?? '';
        $this->activo            = $lote->activo;

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize($this->modoEdicion ? 'campos.lotes.editar' : 'campos.lotes.crear');

        $datos = $this->validate();

        foreach (['codigo', 'superficie_ha', 'latitud', 'longitud', 'descripcion'] as $campo) {
            if (array_key_exists($campo, $datos) && $datos[$campo] === '') {
                $datos[$campo] = null;
            }
        }

        if ($this->modoEdicion) {
            $lote = Lote::findOrFail($this->loteEditandoId);
            $lote->update(array_merge($datos, ['activo' => $this->activo]));
            session()->flash('success', "Lote \"{$lote->nombre}\" actualizado correctamente.");
        } else {
            $lote = Lote::create($datos);
            session()->flash('success', "Lote \"{$lote->nombre}\" creado correctamente.");
        }

        $this->cerrarModal();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('campos.lotes.editar');

        $lote = Lote::findOrFail($id);
        $lote->update(['activo' => ! $lote->activo]);

        $estado = $lote->activo ? 'activado' : 'desactivado';
        session()->flash('success', "Lote \"{$lote->nombre}\" {$estado}.");
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'loteEditandoId', 'modoEdicion',
            'nombre', 'codigo', 'id_establecimiento',
            'superficie_ha', 'latitud', 'longitud', 'descripcion',
        ]);
        $this->tipo   = 'agricola';
        $this->activo = true;
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $lotes = Lote::query()
            ->with('establecimiento')
            ->when($this->busqueda, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('nombre', 'like', "%{$this->busqueda}%")
                          ->orWhere('codigo', 'like', "%{$this->busqueda}%");
                });
            })
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroTipo, fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroActivo !== '', fn ($q) => $q->where('activo', $this->filtroActivo === '1'))
            ->orderBy('nombre')
            ->paginate(15);

        $establecimientos = Establecimiento::query()
            ->activos()
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('livewire.campos.gestion-lotes', [
            'lotes'            => $lotes,
            'establecimientos' => $establecimientos,
            'tiposLote'        => self::TIPOS,
        ]);
    }
}
