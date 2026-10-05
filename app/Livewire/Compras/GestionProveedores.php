<?php

namespace App\Livewire\Compras;

use App\Models\Proveedor;
use App\Models\Rubro;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class GestionProveedores extends Component
{
    use WithPagination;

    public string $busqueda = '';

    public string $filtroRubro = '';

    public string $filtroActivo = '';

    public bool $modalAbierto = false;

    public bool $modoEdicion = false;

    public ?string $proveedorEditandoId = null;

    public string $nombre = '';

    public string $razon_social = '';

    public string $cuit = '';

    public string $rubro = '';

    public string $actividad = '';

    public string $telefono = '';

    public string $email = '';

    public string $direccion = '';

    public string $ciudad = '';

    public string $provincia = '';

    public string $observaciones = '';

    public bool $activo = true;

    public bool $catalogoAbierto = false;

    #[Locked]
    public string $rubroEditando = '';

    public string $nuevoRubroNombre = '';

    public array $nuevoRubroActividades = ['general'];

    public string $nuevoRubroDefault = 'general';

    public function abrirCatalogo(): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $this->catalogoAbierto = ! $this->catalogoAbierto;
        $this->nuevoRubro();
    }

    public function nuevoRubro(): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $this->rubroEditando = '';
        $this->nuevoRubroNombre = '';
        $this->nuevoRubroActividades = ['general'];
        $this->nuevoRubroDefault = 'general';
        $this->resetValidation();
    }

    public function editarRubro(string $codigo): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $rubro = Rubro::catalogo()[$codigo] ?? null;
        abort_unless($rubro, 404);
        $this->rubroEditando = $codigo;
        $this->nuevoRubroNombre = $rubro['nombre'];
        $this->nuevoRubroActividades = $rubro['actividades'];
        $this->nuevoRubroDefault = $rubro['actividad_default'];
        $this->resetValidation();
    }

    public function guardarRubro(): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $empresaId = Proveedor::resolverEmpresaActiva();
        abort_unless($empresaId, 403);
        $this->nuevoRubroNombre = trim($this->nuevoRubroNombre);
        $this->validate([
            'nuevoRubroNombre' => 'required|string|max:100',
            'nuevoRubroActividades' => 'required|array|min:1',
            'nuevoRubroActividades.*' => ['required', Rule::in(array_keys(Proveedor::ACTIVIDADES))],
            'nuevoRubroDefault' => ['required', Rule::in($this->nuevoRubroActividades)],
        ]);
        $codigo = $this->rubroEditando ?: Str::slug($this->nuevoRubroNombre, '_');
        if ($codigo === '' || strlen($codigo) > 60) {
            $this->addError('nuevoRubroNombre', 'Usá un nombre que genere un código de hasta 60 caracteres.');

            return;
        }
        $catalogo = Rubro::catalogo();
        foreach ($catalogo as $key => $existente) {
            if ($key !== $this->rubroEditando && (Str::slug($existente['nombre'], '_') === Str::slug($this->nuevoRubroNombre, '_') || $key === $codigo)) {
                $this->addError('nuevoRubroNombre', 'Ya existe un rubro con ese nombre. Editalo desde el listado.');

                return;
            }
        }
        Rubro::withoutGlobalScope('empresa')->updateOrCreate(
            ['id_empresa' => $empresaId, 'codigo' => $codigo],
            ['nombre' => $this->nuevoRubroNombre, 'actividades' => array_values(array_unique($this->nuevoRubroActividades)), 'actividad_default' => $this->nuevoRubroDefault],
        );
        if ($this->rubro === $codigo) {
            $this->updatedRubro();
        }
        $this->nuevoRubro();
        session()->flash('success', 'Rubro guardado. Disponible en proveedores y compras de esta empresa.');
    }

    public function updatedRubro(): void
    {
        $this->actividad = Rubro::predeterminada($this->rubro) ?? '';
    }

    private function actividadesDelProveedor(): array
    {
        $permitidas = Rubro::actividadesPermitidas($this->rubro);
        // Conservar clasificaciones históricas al editar sin cambiar de rubro.
        if ($this->proveedorEditandoId) {
            $anterior = Proveedor::find($this->proveedorEditandoId);
            if ($anterior && $anterior->rubro === $this->rubro && $anterior->actividad) {
                $permitidas[] = $anterior->actividad;
            }
        }

        return array_intersect_key(Proveedor::ACTIVIDADES, array_flip($permitidas));
    }

    protected function rules(): array
    {
        return [
            'nombre' => 'required|string|max:150',
            'razon_social' => 'nullable|string|max:200',
            'cuit' => ['nullable', 'digits:11', function ($attribute, $value, $fail) {
                if (Proveedor::porCuit($value)->when($this->proveedorEditandoId, fn ($q) => $q->where('id', '!=', $this->proveedorEditandoId))->exists()) {
                    $fail('Ya existe un proveedor con ese CUIT en esta empresa. Editá su clasificación.');
                }
            }],
            'rubro' => 'nullable|in:'.implode(',', array_keys(Rubro::opciones())),
            'actividad' => 'nullable|in:'.implode(',', array_keys($this->actividadesDelProveedor())),
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:200',
            'ciudad' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:80',
            'observaciones' => 'nullable|string',
            'activo' => 'boolean',
        ];
    }

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroRubro(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroActivo(): void
    {
        $this->resetPage();
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $this->resetForm();
        $this->modoEdicion = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('compras.proveedores.gestionar');
        $p = Proveedor::findOrFail($id);
        $this->proveedorEditandoId = $id;
        $this->nombre = $p->nombre;
        $this->razon_social = $p->razon_social ?? '';
        $this->cuit = $p->cuit ?? '';
        $this->rubro = $p->rubro ?? '';
        $this->actividad = $p->actividadPredeterminada() ?? '';
        $this->telefono = $p->telefono ?? '';
        $this->email = $p->email ?? '';
        $this->direccion = $p->direccion ?? '';
        $this->ciudad = $p->ciudad ?? '';
        $this->provincia = $p->provincia ?? '';
        $this->observaciones = $p->observaciones ?? '';
        $this->activo = (bool) $p->activo;
        $this->modoEdicion = true;
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
        $this->cuit = Proveedor::normalizarCuit($this->cuit);
        $this->validate();

        $data = [
            'nombre' => $this->nombre,
            'razon_social' => $this->razon_social ?: null,
            'cuit' => $this->cuit ?: null,
            'rubro' => $this->rubro ?: null,
            'actividad' => $this->actividad ?: null,
            'telefono' => $this->telefono ?: null,
            'email' => $this->email ?: null,
            'direccion' => $this->direccion ?: null,
            'ciudad' => $this->ciudad ?: null,
            'provincia' => $this->provincia ?: null,
            'observaciones' => $this->observaciones ?: null,
            'activo' => $this->activo,
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
        $p->update(['activo' => ! $p->activo]);
        session()->flash('success', $p->activo ? 'Proveedor reactivado.' : 'Proveedor dado de baja.');
    }

    private function resetForm(): void
    {
        $this->proveedorEditandoId = null;
        $this->nombre = '';
        $this->razon_social = '';
        $this->cuit = '';
        $this->rubro = '';
        $this->actividad = '';
        $this->telefono = '';
        $this->email = '';
        $this->direccion = '';
        $this->ciudad = '';
        $this->provincia = '';
        $this->observaciones = '';
        $this->activo = true;
        $this->resetValidation();
    }

    public function render()
    {
        $proveedores = Proveedor::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) => $q->where('nombre', 'like', "%{$this->busqueda}%")
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
            'rubros' => Rubro::opciones(),
            'catalogoRubros' => Rubro::catalogo(),
            'actividadesProveedor' => $this->actividadesDelProveedor(),
            'actividades' => Proveedor::ACTIVIDADES,
        ]);
    }
}
