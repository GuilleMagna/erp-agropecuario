<?php

namespace App\Livewire\Ventas;

use App\Models\Campana;
use App\Models\Establecimiento;
use App\Models\VentaGrano;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionVentasGranos extends Component
{
    use WithPagination;

    public string $busqueda        = '';
    public string $filtroCereal    = '';
    public string $filtroEstado    = '';
    public string $filtroFechaDesde = '';
    public string $filtroFechaHasta = '';

    public bool    $modalAbierto  = false;
    public bool    $modoEdicion   = false;
    public ?string $ventaEditandoId = null;

    public string $id_establecimiento = '';
    public string $id_campana         = '';
    public string $comprador          = '';
    public string $cuit_comprador     = '';
    public string $cereal             = '';
    public string $tipo_venta         = 'disponible';
    public string $corredor           = '';
    public string $numero_comprobante = '';
    public string $fecha              = '';
    public string $fecha_entrega      = '';
    public string $cantidad_tn        = '';
    public string $precio_tn          = '';
    public string $moneda             = 'USD';
    public string $importe_total      = '0.00';
    public string $estado             = 'confirmada';
    public string $observaciones      = '';

    protected function rules(): array
    {
        return [
            'id_establecimiento' => 'nullable|exists:establecimientos,id',
            'id_campana'         => 'nullable|exists:campanas,id',
            'comprador'          => 'nullable|string|max:200',
            'cuit_comprador'     => 'nullable|string|max:20',
            'cereal'             => 'required|in:' . implode(',', array_keys(VentaGrano::CEREALES)),
            'tipo_venta'         => 'required|in:' . implode(',', array_keys(VentaGrano::TIPOS_VENTA)),
            'corredor'           => 'nullable|string|max:150',
            'numero_comprobante' => 'nullable|string|max:50',
            'fecha'              => 'required|date',
            'fecha_entrega'      => 'nullable|date',
            'cantidad_tn'        => 'required|numeric|min:0.001',
            'precio_tn'          => 'required|numeric|min:0',
            'moneda'             => 'required|in:' . implode(',', array_keys(VentaGrano::MONEDAS)),
            'estado'             => 'required|in:' . implode(',', array_keys(VentaGrano::ESTADOS)),
            'observaciones'      => 'nullable|string',
        ];
    }

    public function updatedBusqueda(): void     { $this->resetPage(); }
    public function updatedFiltroCereal(): void  { $this->resetPage(); }
    public function updatedFiltroEstado(): void  { $this->resetPage(); }
    public function updatedFiltroFechaDesde(): void { $this->resetPage(); }
    public function updatedFiltroFechaHasta(): void { $this->resetPage(); }

    public function updatedCantidadTn(): void  { $this->calcularImporte(); }
    public function updatedPrecioTn(): void    { $this->calcularImporte(); }

    private function calcularImporte(): void
    {
        if ($this->cantidad_tn !== '' && $this->precio_tn !== '') {
            $this->importe_total = number_format(
                (float) $this->cantidad_tn * (float) $this->precio_tn,
                2, '.', ''
            );
        }
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('ventas.granos.registrar');
        $this->resetForm();
        $this->fecha       = now()->format('Y-m-d');
        $this->modoEdicion = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('ventas.granos.registrar');
        $venta = VentaGrano::findOrFail($id);
        $this->ventaEditandoId    = $id;
        $this->id_establecimiento = $venta->id_establecimiento ?? '';
        $this->id_campana         = $venta->id_campana ?? '';
        $this->comprador          = $venta->comprador ?? '';
        $this->cuit_comprador     = $venta->cuit_comprador ?? '';
        $this->cereal             = $venta->cereal;
        $this->tipo_venta         = $venta->tipo_venta;
        $this->corredor           = $venta->corredor ?? '';
        $this->numero_comprobante = $venta->numero_comprobante ?? '';
        $this->fecha              = $venta->fecha->format('Y-m-d');
        $this->fecha_entrega      = $venta->fecha_entrega?->format('Y-m-d') ?? '';
        $this->cantidad_tn        = (string) $venta->cantidad_tn;
        $this->precio_tn          = (string) $venta->precio_tn;
        $this->moneda             = $venta->moneda;
        $this->importe_total      = (string) $venta->importe_total;
        $this->estado             = $venta->estado;
        $this->observaciones      = $venta->observaciones ?? '';
        $this->modoEdicion        = true;
        $this->modalAbierto       = true;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function guardar(): void
    {
        Gate::authorize('ventas.granos.registrar');
        $this->calcularImporte();
        $this->validate();

        $data = [
            'id_establecimiento' => $this->id_establecimiento ?: null,
            'id_campana'         => $this->id_campana ?: null,
            'comprador'          => $this->comprador ?: null,
            'cuit_comprador'     => $this->cuit_comprador ?: null,
            'cereal'             => $this->cereal,
            'tipo_venta'         => $this->tipo_venta,
            'corredor'           => $this->corredor ?: null,
            'numero_comprobante' => $this->numero_comprobante ?: null,
            'fecha'              => $this->fecha,
            'fecha_entrega'      => $this->fecha_entrega ?: null,
            'cantidad_tn'        => (float) $this->cantidad_tn,
            'precio_tn'          => (float) $this->precio_tn,
            'moneda'             => $this->moneda,
            'importe_total'      => (float) $this->importe_total,
            'estado'             => $this->estado,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            VentaGrano::findOrFail($this->ventaEditandoId)->update($data);
            session()->flash('success', 'Venta de granos actualizada correctamente.');
        } else {
            VentaGrano::create($data);
            session()->flash('success', 'Venta de granos registrada correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function cambiarEstado(string $id, string $estado): void
    {
        if ($estado === 'cobrada') {
            Gate::authorize('ventas.granos.aprobar');
        } else {
            Gate::authorize('ventas.granos.registrar');
        }

        VentaGrano::findOrFail($id)->update(['estado' => $estado]);
        session()->flash('success', 'Estado de venta actualizado.');
    }

    private function resetForm(): void
    {
        $this->ventaEditandoId    = null;
        $this->id_establecimiento = '';
        $this->id_campana         = '';
        $this->comprador          = '';
        $this->cuit_comprador     = '';
        $this->cereal             = '';
        $this->tipo_venta         = 'disponible';
        $this->corredor           = '';
        $this->numero_comprobante = '';
        $this->fecha              = '';
        $this->fecha_entrega      = '';
        $this->cantidad_tn        = '';
        $this->precio_tn          = '';
        $this->moneda             = 'USD';
        $this->importe_total      = '0.00';
        $this->estado             = 'confirmada';
        $this->observaciones      = '';
        $this->resetValidation();
    }

    public function render()
    {
        $ventas = VentaGrano::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) =>
                $q->where('comprador', 'like', "%{$this->busqueda}%")
                  ->orWhere('numero_comprobante', 'like', "%{$this->busqueda}%")
                  ->orWhere('corredor', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroCereal, fn ($q) => $q->where('cereal', $this->filtroCereal))
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroFechaDesde, fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta))
            ->with(['establecimiento', 'campana'])
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $establecimientos = Establecimiento::orderBy('nombre')->get();
        $campanas         = Campana::activos()->orderBy('nombre')->get();

        return view('livewire.ventas.gestion-ventas-granos', [
            'ventas'          => $ventas,
            'establecimientos'=> $establecimientos,
            'campanas'        => $campanas,
            'cereales'        => VentaGrano::CEREALES,
            'tiposVenta'      => VentaGrano::TIPOS_VENTA,
            'monedas'         => VentaGrano::MONEDAS,
            'estados'         => VentaGrano::ESTADOS,
        ]);
    }
}
