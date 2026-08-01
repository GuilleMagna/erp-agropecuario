<?php

namespace App\Livewire\Ventas;

use App\Models\CategoriaVenta;
use App\Models\Comprador;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCompradores extends Component
{
    use WithPagination;

    public string $busqueda = '';

    public string $filtroCategoria = '';

    public string $filtroActivo = '';

    public bool $modalAbierto = false;

    public bool $modoEdicion = false;

    public ?string $compradorEditandoId = null;

    public string $nombre = '';

    public string $cuit = '';

    public string $id_categoria_venta = '';

    public bool $activo = true;

    // Alta rápida de categoría, sin salir del modal de comprador.
    public bool $formNuevaCategoriaAbierto = false;

    public string $nuevaCategoriaNombre = '';

    public string $nuevaCategoriaTipo = 'animales_kg';

    protected function rules(): array
    {
        return [
            'nombre' => 'required|string|max:200',
            'cuit' => 'nullable|string|max:20',
            'id_categoria_venta' => 'nullable|exists:categorias_venta,id',
        ];
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroCategoria(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroActivo(): void
    {
        $this->resetPage();
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('ventas.compradores.gestionar');
        $this->limpiarFormulario();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('ventas.compradores.gestionar');

        $comprador = Comprador::findOrFail($id);

        $this->compradorEditandoId = $id;
        $this->modoEdicion = true;
        $this->nombre = $comprador->nombre;
        $this->cuit = $comprador->cuit ?? '';
        $this->id_categoria_venta = $comprador->id_categoria_venta ?? '';
        $this->activo = $comprador->activo;

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('ventas.compradores.gestionar');

        $datos = $this->validate();
        $datos['cuit'] = $datos['cuit'] ?: null;
        $datos['id_categoria_venta'] = $datos['id_categoria_venta'] ?: null;
        $datos['activo'] = $this->activo;

        if ($this->modoEdicion) {
            $comprador = Comprador::findOrFail($this->compradorEditandoId);
            $comprador->update($datos);
            session()->flash('success', "Comprador \"{$comprador->nombre}\" actualizado correctamente.");
        } else {
            $comprador = Comprador::create($datos);
            session()->flash('success', "Comprador \"{$comprador->nombre}\" creado correctamente.");
        }

        $this->cerrarModal();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('ventas.compradores.gestionar');

        $comprador = Comprador::findOrFail($id);
        $comprador->update(['activo' => ! $comprador->activo]);

        $estado = $comprador->activo ? 'activado' : 'desactivado';
        session()->flash('success', "Comprador \"{$comprador->nombre}\" {$estado}.");
    }

    public function abrirFormNuevaCategoria(): void
    {
        $this->formNuevaCategoriaAbierto = true;
        $this->nuevaCategoriaNombre = '';
        $this->nuevaCategoriaTipo = 'animales_kg';
    }

    public function guardarNuevaCategoria(): void
    {
        Gate::authorize('ventas.compradores.gestionar');

        $this->validate([
            'nuevaCategoriaNombre' => 'required|string|max:100',
            'nuevaCategoriaTipo' => 'required|in:'.implode(',', array_keys(CategoriaVenta::TIPOS_CANTIDAD)),
        ]);

        $categoria = CategoriaVenta::create([
            'nombre' => $this->nuevaCategoriaNombre,
            'tipo_cantidad' => $this->nuevaCategoriaTipo,
            'activo' => true,
        ]);

        $this->id_categoria_venta = $categoria->id;
        $this->formNuevaCategoriaAbierto = false;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset(['compradorEditandoId', 'modoEdicion', 'nombre', 'cuit', 'id_categoria_venta', 'formNuevaCategoriaAbierto']);
        $this->activo = true;
        $this->resetValidation();
    }

    public function render()
    {
        $compradores = Comprador::query()
            ->with('categoriaVenta')
            ->when($this->busqueda, fn ($q) => $q->where('nombre', 'like', "%{$this->busqueda}%"))
            ->when($this->filtroCategoria, fn ($q) => $q->where('id_categoria_venta', $this->filtroCategoria))
            ->when($this->filtroActivo !== '', fn ($q) => $q->where('activo', $this->filtroActivo === '1'))
            ->orderBy('nombre')
            ->paginate(20);

        return view('livewire.ventas.gestion-compradores', [
            'compradores' => $compradores,
            'categorias' => CategoriaVenta::activas()->orderBy('nombre')->get(),
            'tiposCantidad' => CategoriaVenta::TIPOS_CANTIDAD,
        ]);
    }
}
