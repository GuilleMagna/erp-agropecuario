<?php

namespace App\Livewire\Agricultura;

use App\Models\Campana;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCampanas extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda     = '';
    public string $filtroActivo = '';

    // Estado del modal
    public bool    $modalAbierto   = false;
    public bool    $modoEdicion    = false;
    public ?string $campanaEditandoId = null;

    // Campos del formulario
    public string $nombre      = '';
    public string $descripcion = '';
    public bool   $activo      = true;

    protected array $messages = [
        'nombre.required' => 'El nombre de la campaña es obligatorio.',
        'nombre.max'      => 'El nombre no puede superar 100 caracteres.',
    ];

    protected function rules(): array
    {
        return [
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:1000',
        ];
    }

    public function updatingBusqueda(): void    { $this->resetPage(); }
    public function updatingFiltroActivo(): void { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('agricultura.campanas.gestionar');
        $this->limpiarFormulario();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('agricultura.campanas.gestionar');

        $campana = Campana::findOrFail($id);

        $this->campanaEditandoId = $id;
        $this->modoEdicion       = true;
        $this->nombre            = $campana->nombre;
        $this->descripcion       = $campana->descripcion ?? '';
        $this->activo            = $campana->activo;

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('agricultura.campanas.gestionar');

        $datos = $this->validate();
        if ($datos['descripcion'] === '') $datos['descripcion'] = null;

        if ($this->modoEdicion) {
            $campana = Campana::findOrFail($this->campanaEditandoId);
            $campana->update(array_merge($datos, ['activo' => $this->activo]));
            session()->flash('success', "Campaña \"{$campana->nombre}\" actualizada correctamente.");
        } else {
            $campana = Campana::create($datos);
            session()->flash('success', "Campaña \"{$campana->nombre}\" creada correctamente.");
        }

        $this->cerrarModal();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('agricultura.campanas.gestionar');

        $campana = Campana::findOrFail($id);
        $campana->update(['activo' => ! $campana->activo]);

        $estado = $campana->activo ? 'activada' : 'desactivada';
        session()->flash('success', "Campaña \"{$campana->nombre}\" {$estado}.");
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset(['campanaEditandoId', 'modoEdicion', 'nombre', 'descripcion']);
        $this->activo = true;
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $campanas = Campana::query()
            ->withCount(['siembras', 'labores'])
            ->when($this->busqueda, fn ($q) => $q->where('nombre', 'like', "%{$this->busqueda}%"))
            ->when($this->filtroActivo !== '', fn ($q) => $q->where('activo', $this->filtroActivo === '1'))
            ->orderByDesc('nombre')
            ->paginate(15);

        return view('livewire.agricultura.gestion-campanas', [
            'campanas' => $campanas,
        ]);
    }
}
