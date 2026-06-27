<?php

namespace App\Livewire\Rrhh;

use App\Models\Empleado;
use App\Models\Establecimiento;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionPersonal extends Component
{
    use WithPagination;

    public string $busqueda         = '';
    public string $filtroContrato   = '';
    public string $filtroEstablecimiento = '';
    public string $filtroActivo     = '1';

    public bool    $modalAbierto       = false;
    public bool    $modoEdicion        = false;
    public ?string $empleadoEditandoId = null;

    public string $nombre          = '';
    public string $apellido        = '';
    public string $dni             = '';
    public string $cuil            = '';
    public string $tipo_contrato   = 'jornal';
    public string $categoria       = '';
    public string $id_establecimiento = '';
    public string $fecha_ingreso   = '';
    public string $fecha_egreso    = '';
    public string $sueldo_base     = '';
    public string $telefono        = '';
    public string $email           = '';
    public string $direccion       = '';
    public string $cbu             = '';
    public string $banco           = '';
    public bool   $activo          = true;
    public string $observaciones   = '';

    protected function rules(): array
    {
        return [
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'dni'              => 'nullable|string|max:20',
            'cuil'             => 'nullable|string|max:20',
            'tipo_contrato'    => 'required|in:' . implode(',', array_keys(Empleado::TIPOS_CONTRATO)),
            'categoria'        => 'nullable|in:' . implode(',', array_keys(Empleado::CATEGORIAS)),
            'id_establecimiento' => 'nullable|exists:establecimientos,id',
            'fecha_ingreso'    => 'required|date',
            'fecha_egreso'     => 'nullable|date|after_or_equal:fecha_ingreso',
            'sueldo_base'      => 'nullable|numeric|min:0',
            'telefono'         => 'nullable|string|max:30',
            'email'            => 'nullable|email|max:100',
            'direccion'        => 'nullable|string|max:200',
            'cbu'              => 'nullable|string|max:30',
            'banco'            => 'nullable|string|max:100',
            'activo'           => 'boolean',
            'observaciones'    => 'nullable|string',
        ];
    }

    public function updatedBusqueda(): void             { $this->resetPage(); }
    public function updatedFiltroContrato(): void       { $this->resetPage(); }
    public function updatedFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatedFiltroActivo(): void         { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('rrhh.personal.gestionar');
        $this->resetForm();
        $this->fecha_ingreso = now()->format('Y-m-d');
        $this->modoEdicion   = false;
        $this->modalAbierto  = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('rrhh.personal.gestionar');
        $emp = Empleado::findOrFail($id);
        $this->empleadoEditandoId   = $id;
        $this->nombre               = $emp->nombre;
        $this->apellido             = $emp->apellido;
        $this->dni                  = $emp->dni ?? '';
        $this->cuil                 = $emp->cuil ?? '';
        $this->tipo_contrato        = $emp->tipo_contrato;
        $this->categoria            = $emp->categoria ?? '';
        $this->id_establecimiento   = $emp->id_establecimiento ?? '';
        $this->fecha_ingreso        = $emp->fecha_ingreso->format('Y-m-d');
        $this->fecha_egreso         = $emp->fecha_egreso?->format('Y-m-d') ?? '';
        $this->sueldo_base          = $emp->sueldo_base !== null ? (string) $emp->sueldo_base : '';
        $this->telefono             = $emp->telefono ?? '';
        $this->email                = $emp->email ?? '';
        $this->direccion            = $emp->direccion ?? '';
        $this->cbu                  = $emp->cbu ?? '';
        $this->banco                = $emp->banco ?? '';
        $this->activo               = (bool) $emp->activo;
        $this->observaciones        = $emp->observaciones ?? '';
        $this->modoEdicion          = true;
        $this->modalAbierto         = true;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function guardar(): void
    {
        Gate::authorize('rrhh.personal.gestionar');
        $this->validate();

        $data = [
            'nombre'             => $this->nombre,
            'apellido'           => $this->apellido,
            'dni'                => $this->dni ?: null,
            'cuil'               => $this->cuil ?: null,
            'tipo_contrato'      => $this->tipo_contrato,
            'categoria'          => $this->categoria ?: null,
            'id_establecimiento' => $this->id_establecimiento ?: null,
            'fecha_ingreso'      => $this->fecha_ingreso,
            'fecha_egreso'       => $this->fecha_egreso ?: null,
            'sueldo_base'        => $this->sueldo_base !== '' ? (float) $this->sueldo_base : null,
            'telefono'           => $this->telefono ?: null,
            'email'              => $this->email ?: null,
            'direccion'          => $this->direccion ?: null,
            'cbu'                => $this->cbu ?: null,
            'banco'              => $this->banco ?: null,
            'activo'             => $this->activo,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            Empleado::findOrFail($this->empleadoEditandoId)->update($data);
            session()->flash('success', 'Empleado actualizado correctamente.');
        } else {
            Empleado::create($data);
            session()->flash('success', 'Empleado registrado correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('rrhh.personal.gestionar');
        $emp = Empleado::findOrFail($id);
        $emp->update(['activo' => !$emp->activo]);
        session()->flash('success', $emp->activo ? 'Empleado reactivado.' : 'Empleado dado de baja.');
    }

    private function resetForm(): void
    {
        $this->empleadoEditandoId = null;
        $this->nombre             = '';
        $this->apellido           = '';
        $this->dni                = '';
        $this->cuil               = '';
        $this->tipo_contrato      = 'jornal';
        $this->categoria          = '';
        $this->id_establecimiento = '';
        $this->fecha_ingreso      = '';
        $this->fecha_egreso       = '';
        $this->sueldo_base        = '';
        $this->telefono           = '';
        $this->email              = '';
        $this->direccion          = '';
        $this->cbu                = '';
        $this->banco              = '';
        $this->activo             = true;
        $this->observaciones      = '';
        $this->resetValidation();
    }

    public function render()
    {
        $empleados = Empleado::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) =>
                $q->where('nombre', 'like', "%{$this->busqueda}%")
                  ->orWhere('apellido', 'like', "%{$this->busqueda}%")
                  ->orWhere('dni', 'like', "%{$this->busqueda}%")
                  ->orWhere('cuil', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroContrato, fn ($q) => $q->where('tipo_contrato', $this->filtroContrato))
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroActivo !== '', fn ($q) => $q->where('activo', (bool) $this->filtroActivo))
            ->with('establecimiento')
            ->withCount('jornales')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(20);

        $establecimientos = Establecimiento::orderBy('nombre')->get();

        return view('livewire.rrhh.gestion-personal', [
            'empleados'       => $empleados,
            'establecimientos'=> $establecimientos,
            'tiposContrato'   => Empleado::TIPOS_CONTRATO,
            'categorias'      => Empleado::CATEGORIAS,
        ]);
    }
}
