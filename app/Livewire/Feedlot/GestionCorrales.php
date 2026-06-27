<?php

namespace App\Livewire\Feedlot;

use App\Models\Corral;
use App\Models\Establecimiento;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCorrales extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // Filtros
    public string $busqueda              = '';
    public string $filtroEstablecimiento = '';
    public string $filtroActivo          = '1';

    // Modal
    public bool    $modalAbierto    = false;
    public bool    $modoEdicion     = false;
    public ?string $corralEditandoId = null;

    // Campos
    public string  $nombre           = '';
    public string  $codigo           = '';
    public string  $idEstablecimiento = '';
    public int     $capacidadCabezas = 0;
    public string  $superficieM2     = '';
    public bool    $activo           = true;
    public string  $observaciones    = '';

    protected function rules(): array
    {
        return [
            'nombre'           => 'required|string|max:100',
            'codigo'           => 'nullable|string|max:20',
            'idEstablecimiento'=> 'nullable|exists:establecimientos,id',
            'capacidadCabezas' => 'required|integer|min:1',
            'superficieM2'     => 'nullable|numeric|min:0',
            'observaciones'    => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'nombre.required'          => 'El nombre es obligatorio.',
        'capacidadCabezas.required'=> 'La capacidad es obligatoria.',
        'capacidadCabezas.min'     => 'La capacidad debe ser al menos 1 cabeza.',
    ];

    public function updatingBusqueda(): void              { $this->resetPage(); }
    public function updatingFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatingFiltroActivo(): void          { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('feedlot.corrales.gestionar');
        $this->limpiarFormulario();
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('feedlot.corrales.gestionar');

        $corral = Corral::findOrFail($id);

        $this->corralEditandoId  = $id;
        $this->nombre            = $corral->nombre;
        $this->codigo            = $corral->codigo ?? '';
        $this->idEstablecimiento = $corral->id_establecimiento ?? '';
        $this->capacidadCabezas  = $corral->capacidad_cabezas;
        $this->superficieM2      = $corral->superficie_m2 !== null ? (string)$corral->superficie_m2 : '';
        $this->activo            = $corral->activo;
        $this->observaciones     = $corral->observaciones ?? '';

        $this->modoEdicion  = true;
        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        $this->validate();

        $datos = [
            'nombre'            => $this->nombre,
            'codigo'            => $this->codigo ?: null,
            'id_establecimiento'=> $this->idEstablecimiento ?: null,
            'capacidad_cabezas' => $this->capacidadCabezas,
            'superficie_m2'     => $this->superficieM2 !== '' ? (float)$this->superficieM2 : null,
            'observaciones'     => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            Gate::authorize('feedlot.corrales.gestionar');
            $corral = Corral::findOrFail($this->corralEditandoId);
            $corral->update(array_merge($datos, ['activo' => $this->activo]));
            session()->flash('success', "Corral '{$corral->nombre}' actualizado correctamente.");
        } else {
            Gate::authorize('feedlot.corrales.gestionar');
            Corral::create(array_merge($datos, ['id_empresa' => auth()->user()->id_empresa]));
            session()->flash('success', "Corral '{$this->nombre}' creado correctamente.");
        }

        $this->cerrarModal();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('feedlot.corrales.gestionar');

        $corral = Corral::findOrFail($id);
        $corral->activo = !$corral->activo;
        $corral->save();

        $estado = $corral->activo ? 'habilitado' : 'deshabilitado';
        session()->flash('success', "Corral '{$corral->nombre}' {$estado}.");
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'corralEditandoId', 'nombre', 'codigo', 'idEstablecimiento',
            'superficieM2', 'observaciones',
        ]);
        $this->capacidadCabezas = 0;
        $this->activo           = true;
        $this->resetValidation();
    }

    public function render()
    {
        $corrales = Corral::query()
            ->with(['establecimiento', 'tropasActivas'])
            ->withSum('tropasActivas as ocupacion_actual', 'cantidad_cabezas')
            ->when($this->busqueda, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('nombre', 'like', "%{$this->busqueda}%")
                       ->orWhere('codigo', 'like', "%{$this->busqueda}%")
                )
            )
            ->when($this->filtroEstablecimiento, fn($q) =>
                $q->where('id_establecimiento', $this->filtroEstablecimiento)
            )
            ->when($this->filtroActivo !== '', fn($q) =>
                $q->where('activo', $this->filtroActivo === '1')
            )
            ->orderBy('nombre')
            ->paginate(15);

        $establecimientos = Establecimiento::orderBy('nombre')->get();

        return view('livewire.feedlot.gestion-corrales', compact('corrales', 'establecimientos'));
    }
}
