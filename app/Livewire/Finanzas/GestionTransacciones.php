<?php

namespace App\Livewire\Finanzas;

use App\Models\Cuenta;
use App\Models\Establecimiento;
use App\Models\Transaccion;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionTransacciones extends Component
{
    use WithPagination;

    public string $busqueda              = '';
    public string $filtroCuenta          = '';
    public string $filtroTipo            = '';
    public string $filtroCategoria       = '';
    public string $filtroEstablecimiento = '';
    public string $filtroFechaDesde      = '';
    public string $filtroFechaHasta      = '';

    public bool    $modalAbierto          = false;
    public bool    $modoEdicion           = false;
    public ?string $transaccionEditandoId = null;

    public string $id_cuenta           = '';
    public string $tipo                = 'egreso';
    public string $categoria           = '';
    public string $concepto            = '';
    public string $importe             = '';
    public string $fecha               = '';
    public string $id_establecimiento  = '';
    public string $numero_comprobante  = '';
    public string $observaciones       = '';

    protected function rules(): array
    {
        $validas = implode(',', array_merge(
            array_keys(Transaccion::CATEGORIAS_INGRESO),
            array_keys(Transaccion::CATEGORIAS_EGRESO)
        ));

        return [
            'id_cuenta'          => 'required|exists:cuentas,id',
            'tipo'               => 'required|in:ingreso,egreso',
            'categoria'          => 'required|in:' . $validas,
            'concepto'           => 'required|string|max:200',
            'importe'            => 'required|numeric|min:0.01',
            'fecha'              => 'required|date',
            'id_establecimiento' => 'nullable|exists:establecimientos,id',
            'numero_comprobante' => 'nullable|string|max:50',
            'observaciones'      => 'nullable|string',
        ];
    }

    public function updatedBusqueda(): void              { $this->resetPage(); }
    public function updatedFiltroCuenta(): void          { $this->resetPage(); }
    public function updatedFiltroTipo(): void            { $this->resetPage(); }
    public function updatedFiltroCategoria(): void       { $this->resetPage(); }
    public function updatedFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatedFiltroFechaDesde(): void      { $this->resetPage(); }
    public function updatedFiltroFechaHasta(): void      { $this->resetPage(); }

    public function updatedTipo(): void
    {
        $this->categoria = '';
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('finanzas.transacciones.crear');
        $this->resetForm();
        $this->fecha       = now()->format('Y-m-d');
        $this->modoEdicion = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('finanzas.transacciones.editar');
        $t = Transaccion::findOrFail($id);
        $this->transaccionEditandoId = $id;
        $this->id_cuenta             = $t->id_cuenta;
        $this->tipo                  = $t->tipo;
        $this->categoria             = $t->categoria;
        $this->concepto              = $t->concepto;
        $this->importe               = (string) $t->importe;
        $this->fecha                 = $t->fecha->format('Y-m-d');
        $this->id_establecimiento    = $t->id_establecimiento ?? '';
        $this->numero_comprobante    = $t->numero_comprobante ?? '';
        $this->observaciones         = $t->observaciones ?? '';
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
        Gate::authorize($this->modoEdicion ? 'finanzas.transacciones.editar' : 'finanzas.transacciones.crear');
        $this->validate();

        $data = [
            'id_cuenta'          => $this->id_cuenta,
            'tipo'               => $this->tipo,
            'categoria'          => $this->categoria,
            'concepto'           => $this->concepto,
            'importe'            => (float) $this->importe,
            'fecha'              => $this->fecha,
            'id_establecimiento' => $this->id_establecimiento ?: null,
            'numero_comprobante' => $this->numero_comprobante ?: null,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            Transaccion::findOrFail($this->transaccionEditandoId)->update($data);
            session()->flash('success', 'Transacción actualizada correctamente.');
        } else {
            Transaccion::create($data);
            session()->flash('success', 'Transacción registrada correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->transaccionEditandoId = null;
        $this->id_cuenta             = '';
        $this->tipo                  = 'egreso';
        $this->categoria             = '';
        $this->concepto              = '';
        $this->importe               = '';
        $this->fecha                 = '';
        $this->id_establecimiento    = '';
        $this->numero_comprobante    = '';
        $this->observaciones         = '';
        $this->resetValidation();
    }

    public function render()
    {
        $transacciones = Transaccion::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) =>
                $q->where('concepto', 'like', "%{$this->busqueda}%")
                  ->orWhere('numero_comprobante', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroCuenta, fn ($q) => $q->where('id_cuenta', $this->filtroCuenta))
            ->when($this->filtroTipo, fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroCategoria, fn ($q) => $q->where('categoria', $this->filtroCategoria))
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroFechaDesde, fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta))
            ->with(['cuenta', 'establecimiento'])
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $cuentasOpciones  = Cuenta::activas()->orderBy('nombre')->get();
        $establecimientos = Establecimiento::orderBy('nombre')->get();

        $categoriasFormulario = $this->tipo === 'ingreso'
            ? Transaccion::CATEGORIAS_INGRESO
            : Transaccion::CATEGORIAS_EGRESO;

        return view('livewire.finanzas.gestion-transacciones', [
            'transacciones'       => $transacciones,
            'cuentasOpciones'     => $cuentasOpciones,
            'establecimientos'    => $establecimientos,
            'categoriasIngreso'   => Transaccion::CATEGORIAS_INGRESO,
            'categoriasEgreso'    => Transaccion::CATEGORIAS_EGRESO,
            'categoriasFormulario' => $categoriasFormulario,
            'todasCategorias'     => array_merge(
                Transaccion::CATEGORIAS_INGRESO,
                Transaccion::CATEGORIAS_EGRESO
            ),
        ]);
    }
}
