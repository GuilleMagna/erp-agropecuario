<?php

namespace App\Livewire\Rrhh;

use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\Jornal;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionJornales extends Component
{
    use WithPagination;

    public string $busqueda         = '';
    public string $filtroEmpleado   = '';
    public string $filtroEstado     = '';
    public string $filtroFechaDesde = '';
    public string $filtroFechaHasta = '';

    public bool    $modalAbierto    = false;
    public bool    $modoEdicion     = false;
    public ?string $jornalEditandoId = null;

    public string $id_empleado        = '';
    public string $id_establecimiento = '';
    public string $fecha              = '';
    public string $tipo_jornada       = 'completa';
    public string $horas_trabajadas   = '';
    public string $tarea              = '';
    public string $importe            = '';
    public string $estado             = 'pendiente';
    public string $observaciones      = '';

    // Sueldo de referencia del empleado seleccionado (solo para mostrar en modal)
    public string $sueldoReferencia = '';

    protected function rules(): array
    {
        return [
            'id_empleado'        => 'nullable|exists:empleados,id',
            'id_establecimiento' => 'nullable|exists:establecimientos,id',
            'fecha'              => 'required|date',
            'tipo_jornada'       => 'required|in:' . implode(',', array_keys(Jornal::TIPOS_JORNADA)),
            'horas_trabajadas'   => 'nullable|numeric|min:0|max:24',
            'tarea'              => 'nullable|string|max:200',
            'importe'            => 'required|numeric|min:0',
            'estado'             => 'required|in:' . implode(',', array_keys(Jornal::ESTADOS)),
            'observaciones'      => 'nullable|string',
        ];
    }

    public function updatedBusqueda(): void        { $this->resetPage(); }
    public function updatedFiltroEmpleado(): void  { $this->resetPage(); }
    public function updatedFiltroEstado(): void    { $this->resetPage(); }
    public function updatedFiltroFechaDesde(): void { $this->resetPage(); }
    public function updatedFiltroFechaHasta(): void { $this->resetPage(); }

    public function updatedIdEmpleado(string $value): void
    {
        if ($value) {
            $emp = Empleado::find($value);
            $this->sueldoReferencia = $emp?->sueldo_base !== null
                ? '$ ' . number_format((float) $emp->sueldo_base, 2, ',', '.')
                : '';
        } else {
            $this->sueldoReferencia = '';
        }
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('rrhh.jornales.registrar');
        $this->resetForm();
        $this->fecha        = now()->format('Y-m-d');
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('rrhh.jornales.registrar');
        $jornal = Jornal::findOrFail($id);
        $this->jornalEditandoId    = $id;
        $this->id_empleado         = $jornal->id_empleado ?? '';
        $this->id_establecimiento  = $jornal->id_establecimiento ?? '';
        $this->fecha               = $jornal->fecha->format('Y-m-d');
        $this->tipo_jornada        = $jornal->tipo_jornada;
        $this->horas_trabajadas    = $jornal->horas_trabajadas !== null ? (string) $jornal->horas_trabajadas : '';
        $this->tarea               = $jornal->tarea ?? '';
        $this->importe             = (string) $jornal->importe;
        $this->estado              = $jornal->estado;
        $this->observaciones       = $jornal->observaciones ?? '';

        if ($jornal->id_empleado) {
            $this->updatedIdEmpleado($jornal->id_empleado);
        }

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
        Gate::authorize('rrhh.jornales.registrar');
        $this->validate();

        $data = [
            'id_empleado'        => $this->id_empleado ?: null,
            'id_establecimiento' => $this->id_establecimiento ?: null,
            'fecha'              => $this->fecha,
            'tipo_jornada'       => $this->tipo_jornada,
            'horas_trabajadas'   => $this->horas_trabajadas !== '' ? (float) $this->horas_trabajadas : null,
            'tarea'              => $this->tarea ?: null,
            'importe'            => (float) $this->importe,
            'estado'             => $this->estado,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            Jornal::findOrFail($this->jornalEditandoId)->update($data);
            session()->flash('success', 'Jornal actualizado correctamente.');
        } else {
            Jornal::create($data);
            session()->flash('success', 'Jornal registrado correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function cambiarEstado(string $id, string $estado): void
    {
        Gate::authorize('rrhh.jornales.registrar');
        Jornal::findOrFail($id)->update(['estado' => $estado]);
        session()->flash('success', 'Estado del jornal actualizado.');
    }

    public function liquidarPendientes(): void
    {
        Gate::authorize('rrhh.jornales.registrar');

        $query = Jornal::query()->where('estado', 'pendiente')
            ->when($this->filtroEmpleado, fn ($q) => $q->where('id_empleado', $this->filtroEmpleado))
            ->when($this->filtroFechaDesde, fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta));

        $count = $query->count();

        if ($count === 0) {
            session()->flash('error', 'No hay jornales pendientes en el filtro actual.');
            return;
        }

        $query->update(['estado' => 'liquidado']);
        session()->flash('success', "{$count} jornal(es) marcado(s) como liquidado(s).");
    }

    private function resetForm(): void
    {
        $this->jornalEditandoId   = null;
        $this->id_empleado        = '';
        $this->id_establecimiento = '';
        $this->fecha              = '';
        $this->tipo_jornada       = 'completa';
        $this->horas_trabajadas   = '';
        $this->tarea              = '';
        $this->importe            = '';
        $this->estado             = 'pendiente';
        $this->observaciones      = '';
        $this->sueldoReferencia   = '';
        $this->resetValidation();
    }

    public function render()
    {
        $jornales = Jornal::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) =>
                $q->where('tarea', 'like', "%{$this->busqueda}%")
                  ->orWhereHas('empleado', fn ($q) =>
                      $q->where('nombre', 'like', "%{$this->busqueda}%")
                        ->orWhere('apellido', 'like', "%{$this->busqueda}%")
                  )
            ))
            ->when($this->filtroEmpleado, fn ($q) => $q->where('id_empleado', $this->filtroEmpleado))
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroFechaDesde, fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta))
            ->with(['empleado', 'establecimiento'])
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        // Totals for current filter (all pages)
        $totalesQuery = Jornal::query()
            ->when($this->filtroEmpleado, fn ($q) => $q->where('id_empleado', $this->filtroEmpleado))
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroFechaDesde, fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta));

        $totalPendiente = (clone $totalesQuery)->where('estado', 'pendiente')->sum('importe');
        $totalLiquidado = (clone $totalesQuery)->where('estado', 'liquidado')->sum('importe');

        $empleadosOpciones = Empleado::activos()->orderBy('apellido')->orderBy('nombre')->get();
        $establecimientos  = Establecimiento::orderBy('nombre')->get();

        return view('livewire.rrhh.gestion-jornales', [
            'jornales'         => $jornales,
            'empleadosOpciones'=> $empleadosOpciones,
            'establecimientos' => $establecimientos,
            'tiposJornada'     => Jornal::TIPOS_JORNADA,
            'estados'          => Jornal::ESTADOS,
            'totalPendiente'   => $totalPendiente,
            'totalLiquidado'   => $totalLiquidado,
        ]);
    }
}
