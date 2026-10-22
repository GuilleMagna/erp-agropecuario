<?php

namespace App\Livewire\Admin;

use App\Models\Establecimiento;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class GestionUbicaciones extends Component
{
    public ?string $editandoId = null;

    public string $nombre = '';

    public string $tipo = 'zona';

    public string $id_padre = '';

    public bool $crearFicha = true;

    public function editar(string $id): void
    {
        Gate::authorize('admin.establecimientos.gestionar');
        $ubicacion = Ubicacion::findOrFail($id);
        $this->editandoId = $id;
        $this->nombre = $ubicacion->nombre;
        $this->tipo = $ubicacion->tipo;
        $this->id_padre = $ubicacion->id_padre ?? '';
        $this->resetValidation();
    }

    public function cancelar(): void
    {
        $this->reset(['editandoId', 'nombre', 'tipo', 'id_padre', 'crearFicha']);
        $this->resetValidation();
    }

    public function guardar(): void
    {
        Gate::authorize('admin.establecimientos.gestionar');
        $this->nombre = trim($this->nombre);
        $this->validate([
            'nombre' => 'required|string|max:150',
            'tipo' => 'required|in:'.implode(',', array_keys(Ubicacion::TIPOS)),
            'id_padre' => 'nullable|exists:ubicaciones,id',
        ]);
        $ubicacion = $this->editandoId ? Ubicacion::findOrFail($this->editandoId) : new Ubicacion;
        if ($ubicacion->exists && $this->tipo !== 'campo' && Establecimiento::withoutGlobalScope('empresa')->where('id_ubicacion', $ubicacion->id)->exists()) {
            $this->addError('tipo', 'Esta ubicación tiene una ficha productiva y debe conservar el tipo Campo.');

            return;
        }
        DB::transaction(function () use ($ubicacion) {
            $ubicacion->fill(['nombre' => $this->nombre, 'tipo' => $this->tipo, 'id_padre' => $this->id_padre ?: null])->save();
            if ($this->tipo === 'campo' && $this->crearFicha
                && ! Establecimiento::withoutGlobalScope('empresa')->where('id_ubicacion', $ubicacion->id)->exists()) {
                $empresa = Establecimiento::resolverEmpresaActiva();
                abort_unless($empresa, 403, 'Seleccioná una empresa para crear la ficha productiva.');
                Establecimiento::create([
                    'id_empresa' => $empresa, 'nombre' => $ubicacion->nombre, 'id_ubicacion' => $ubicacion->id,
                    'zona' => $ubicacion->id_padre, 'tipo_tenencia' => 'propio', 'activo' => true,
                ]);
            }
        });
        $this->cancelar();
        session()->flash('success', 'Ubicación guardada correctamente.');
    }

    public function render()
    {
        Gate::authorize('admin.establecimientos.gestionar');

        return view('livewire.admin.gestion-ubicaciones', [
            'ubicaciones' => Ubicacion::all()->keyBy('id'),
            'opciones' => Ubicacion::opciones(),
            'excluidas' => $this->editandoId ? Ubicacion::descendientes($this->editandoId) : [],
            'tipos' => Ubicacion::TIPOS,
        ]);
    }
}
