<?php

namespace App\Livewire\Finanzas;

use App\Models\Cuenta;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCuentas extends Component
{
    use WithPagination;

    public string $busqueda    = '';
    public string $filtroTipo  = '';
    public string $filtroActiva = '1';

    public bool    $modalAbierto    = false;
    public bool    $modoEdicion     = false;
    public ?string $cuentaEditandoId = null;

    public string $nombre        = '';
    public string $tipo          = 'banco';
    public string $moneda        = 'ARS';
    public string $numero_cuenta = '';
    public string $banco         = '';
    public string $saldo_inicial = '0';
    public bool   $activa        = true;
    public string $observaciones = '';

    protected function rules(): array
    {
        return [
            'nombre'        => 'required|string|max:100',
            'tipo'          => 'required|in:' . implode(',', array_keys(Cuenta::TIPOS)),
            'moneda'        => 'required|in:' . implode(',', array_keys(Cuenta::MONEDAS)),
            'numero_cuenta' => 'nullable|string|max:50',
            'banco'         => 'nullable|string|max:100',
            'saldo_inicial' => 'nullable|numeric',
            'activa'        => 'boolean',
            'observaciones' => 'nullable|string',
        ];
    }

    public function updatedBusqueda(): void   { $this->resetPage(); }
    public function updatedFiltroTipo(): void  { $this->resetPage(); }
    public function updatedFiltroActiva(): void { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('finanzas.cuentas.gestionar');
        $this->resetForm();
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('finanzas.cuentas.gestionar');
        $cuenta = Cuenta::findOrFail($id);
        $this->cuentaEditandoId = $id;
        $this->nombre           = $cuenta->nombre;
        $this->tipo             = $cuenta->tipo;
        $this->moneda           = $cuenta->moneda;
        $this->numero_cuenta    = $cuenta->numero_cuenta ?? '';
        $this->banco            = $cuenta->banco ?? '';
        $this->saldo_inicial    = (string) $cuenta->saldo_inicial;
        $this->activa           = (bool) $cuenta->activa;
        $this->observaciones    = $cuenta->observaciones ?? '';
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
        Gate::authorize('finanzas.cuentas.gestionar');
        $this->validate();

        $data = [
            'nombre'        => $this->nombre,
            'tipo'          => $this->tipo,
            'moneda'        => $this->moneda,
            'numero_cuenta' => $this->numero_cuenta ?: null,
            'banco'         => $this->banco ?: null,
            'saldo_inicial' => $this->saldo_inicial !== '' ? (float) $this->saldo_inicial : 0,
            'activa'        => $this->activa,
            'observaciones' => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            Cuenta::findOrFail($this->cuentaEditandoId)->update($data);
            session()->flash('success', 'Cuenta actualizada correctamente.');
        } else {
            Cuenta::create($data);
            session()->flash('success', 'Cuenta creada correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->cuentaEditandoId = null;
        $this->nombre           = '';
        $this->tipo             = 'banco';
        $this->moneda           = 'ARS';
        $this->numero_cuenta    = '';
        $this->banco            = '';
        $this->saldo_inicial    = '0';
        $this->activa           = true;
        $this->observaciones    = '';
        $this->resetValidation();
    }

    public function render()
    {
        $cuentas = Cuenta::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) =>
                $q->where('nombre', 'like', "%{$this->busqueda}%")
                  ->orWhere('banco', 'like', "%{$this->busqueda}%")
                  ->orWhere('numero_cuenta', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroTipo, fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroActiva !== '', fn ($q) => $q->where('activa', (bool) $this->filtroActiva))
            ->withSum(
                ['transacciones as total_ingresos' => fn ($q) => $q->where('tipo', 'ingreso')],
                'importe'
            )
            ->withSum(
                ['transacciones as total_egresos' => fn ($q) => $q->where('tipo', 'egreso')],
                'importe'
            )
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.finanzas.gestion-cuentas', [
            'cuentas' => $cuentas,
            'tipos'   => Cuenta::TIPOS,
            'monedas' => Cuenta::MONEDAS,
        ]);
    }
}
