<?php

namespace App\Livewire\Compras;

use App\Models\Proveedor;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionProveedores extends Component
{
    use WithPagination;

    public string $busqueda    = '';
    public string $filtroRubro = '';
    public string $filtroActivo = '';

    public bool    $modalAbierto        = false;
    public bool    $modoEdicion         = false;
    public ?string $proveedorEditandoId = null;

    public string $nombre        = '';
    public string $razon_social  = '';
    public string $cuit          = '';
    public string $rubro         = '';
    public string $telefono      = '';
    public string $email         = '';
    public string $direccion     = '';
    public string $ciudad        = '';
    public string $provincia     = '';
    public string $observaciones = '';
    public bool   $activo        = true;

    protected function rules(): array
    {
        return [
            'nombre'        => 'required|string|max:150',
            'razon_social'  => 'nullable|string|max:200',
            'cuit'          => 'nullable|string|max:20',
            'rubro'         => 'nullable|in:' . implode(',', array_keys(Proveedor::RUBROS)),
            'telefono'      => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:100',
            'direccion'     => 'nullable|string|max:200',
            'ciudad'        => 'nullable|string|max:100',
            'provincia'     => 'nullable|string|max:80',
            'observaciones' => 'nullable|string',
            'activo'        => 'boolean',
        ];
    }

    public function updatedBusqueda(): void    { $this->resetPage(); }
    public function updatedFiltroRubro(): void  { $this->resetPage(); }
    public function updatedFiltroActivo(): void { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $this->resetForm();
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $p = Proveedor::findOrFail($id);
        $this->proveedorEditandoId = $id;
        $this->nombre        = $p->nombre;
        $this->razon_social  = $p->razon_social ?? '';
        $this->cuit          = $p->cuit ?? '';
        $this->rubro         = $p->rubro ?? '';
        $this->telefono      = $p->telefono ?? '';
        $this->email         = $p->email ?? '';
        $this->direccion     = $p->direccion ?? '';
        $this->ciudad        = $p->ciudad ?? '';
        $this->provincia     = $p->provincia ?? '';
        $this->observaciones = $p->observaciones ?? '';
        $this->activo        = (bool) $p->activo;
        $this->modoEdicion  = true;
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function guardar(): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $this->validate();

        $data = [
            'nombre'        => $this->nombre,
            'razon_social'  => $this->razon_social ?: null,
            'cuit'          => $this->cuit ?: null,
            'rubro'         => $this->rubro ?: null,
            'telefono'      => $this->telefono ?: null,
            'email'         => $this->email ?: null,
            'direccion'     => $this->direccion ?: null,
            'ciudad'        => $this->ciudad ?: null,
            'provincia'     => $this->provincia ?: null,
            'observaciones' => $this->observaciones ?: null,
            'activo'        => $this->activo,
        ];

        if ($this->modoEdicion) {
            Proveedor::findOrFail($this->proveedorEditandoId)->update($data);
            session()->flash('success', 'Proveedor actualizado correctamente.');
        } else {
            Proveedor::create($data);
            session()->flash('success', 'Proveedor creado correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $p = Proveedor::findOrFail($id);
        $p->update(['activo' => !$p->activo]);
        session()->flash('success', $p->activo ? 'Proveedor reactivado.' : 'Proveedor dado de baja.');
    }

    private function resetForm(): void
    {
        $this->proveedorEditandoId = null;
        $this->nombre              = '';
        $this->razon_social        = '';
        $this->cuit                = '';
        $this->rubro               = '';
        $this->telefono            = '';
        $this->email               = '';
        $this->direccion           = '';
        $this->ciudad              = '';
        $this->provincia           = '';
        $this->observaciones       = '';
        $this->activo              = true;
        $this->resetValidation();
    }

    public function render()
    {
        $proveedores = Proveedor::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) =>
                $q->where('nombre', 'like', "%{$this->busqueda}%")
                  ->orWhere('razon_social', 'like', "%{$this->busqueda}%")
                  ->orWhere('cuit', 'like', "%{$this->busqueda}%")
                  ->orWhere('email', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroRubro, fn ($q) => $q->where('rubro', $this->filtroRubro))
            ->when($this->filtroActivo !== '', fn ($q) => $q->where('activo', (bool) $this->filtroActivo))
            ->withCount('compras')
            ->orderBy('nombre')
            ->paginate(20);

        return view('livewire.compras.gestion-proveedores', [
            'proveedores' => $proveedores,
            'rubros'      => Proveedor::RUBROS,
        ]);
    }
}
