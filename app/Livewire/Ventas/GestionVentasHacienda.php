<?php

namespace App\Livewire\Ventas;

use App\Models\Establecimiento;
use App\Models\VentaHacienda;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionVentasHacienda extends Component
{
    use WithPagination;

    public string $busqueda         = '';
    public string $filtroCategoria  = '';
    public string $filtroEstado     = '';
    public string $filtroFechaDesde = '';
    public string $filtroFechaHasta = '';

    public bool    $modalAbierto    = false;
    public bool    $modoEdicion     = false;
    public ?string $ventaEditandoId = null;

    public string $id_establecimiento = '';
    public string $comprador          = '';
    public string $corredor_feria     = '';
    public string $numero_guia        = '';
    public string $fecha              = '';
    public string $tipo_operacion     = 'terminado';
    public string $categoria          = '';
    public string $cantidad_cabezas   = '';
    public string $peso_promedio_kg   = '';
    public string $peso_total_kg      = '';
    public string $precio_kg          = '';
    public string $precio_cabeza      = '';
    public string $importe_total      = '0.00';
    public string $moneda             = 'ARS';
    public string $estado             = 'confirmada';
    public string $observaciones      = '';

    protected function rules(): array
    {
        return [
            'id_establecimiento' => 'nullable|exists:establecimientos,id',
            'comprador'          => 'nullable|string|max:200',
            'corredor_feria'     => 'nullable|string|max:150',
            'numero_guia'        => 'nullable|string|max:50',
            'fecha'              => 'required|date',
            'tipo_operacion'     => 'required|in:' . implode(',', array_keys(VentaHacienda::TIPOS_OPERACION)),
            'categoria'          => 'required|in:' . implode(',', array_keys(VentaHacienda::CATEGORIAS)),
            'cantidad_cabezas'   => 'required|integer|min:1',
            'peso_promedio_kg'   => 'nullable|numeric|min:0',
            'peso_total_kg'      => 'nullable|numeric|min:0',
            'precio_kg'          => 'nullable|numeric|min:0',
            'precio_cabeza'      => 'nullable|numeric|min:0',
            'importe_total'      => 'required|numeric|min:0',
            'moneda'             => 'required|in:' . implode(',', array_keys(VentaHacienda::MONEDAS)),
            'estado'             => 'required|in:' . implode(',', array_keys(VentaHacienda::ESTADOS)),
            'observaciones'      => 'nullable|string',
        ];
    }

    public function updatedBusqueda(): void        { $this->resetPage(); }
    public function updatedFiltroCategoria(): void  { $this->resetPage(); }
    public function updatedFiltroEstado(): void     { $this->resetPage(); }
    public function updatedFiltroFechaDesde(): void { $this->resetPage(); }
    public function updatedFiltroFechaHasta(): void { $this->resetPage(); }

    public function updatedCantidadCabezas(): void { $this->recalcular(); }
    public function updatedPesoPromedioKg(): void  { $this->recalcular(); }
    public function updatedPrecioKg(): void        { $this->recalcular(); }
    public function updatedPrecioCabeza(): void    { $this->recalcular(); }

    private function recalcular(): void
    {
        $cabezas = (float) ($this->cantidad_cabezas ?: 0);
        $pesoPromedio = (float) ($this->peso_promedio_kg ?: 0);

        if ($cabezas > 0 && $pesoPromedio > 0) {
            $this->peso_total_kg = number_format($cabezas * $pesoPromedio, 2, '.', '');
        }

        $pesoTotal = (float) ($this->peso_total_kg ?: 0);
        $precioKg  = (float) ($this->precio_kg ?: 0);
        $precioCab = (float) ($this->precio_cabeza ?: 0);

        if ($precioKg > 0 && $pesoTotal > 0) {
            $this->importe_total = number_format($pesoTotal * $precioKg, 2, '.', '');
        } elseif ($precioCab > 0 && $cabezas > 0) {
            $this->importe_total = number_format($cabezas * $precioCab, 2, '.', '');
        }
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('ventas.hacienda.registrar');
        $this->resetForm();
        $this->fecha        = now()->format('Y-m-d');
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('ventas.hacienda.registrar');
        $venta = VentaHacienda::findOrFail($id);
        $this->ventaEditandoId    = $id;
        $this->id_establecimiento = $venta->id_establecimiento ?? '';
        $this->comprador          = $venta->comprador ?? '';
        $this->corredor_feria     = $venta->corredor_feria ?? '';
        $this->numero_guia        = $venta->numero_guia ?? '';
        $this->fecha              = $venta->fecha->format('Y-m-d');
        $this->tipo_operacion     = $venta->tipo_operacion;
        $this->categoria          = $venta->categoria;
        $this->cantidad_cabezas   = (string) $venta->cantidad_cabezas;
        $this->peso_promedio_kg   = $venta->peso_promedio_kg !== null ? (string) $venta->peso_promedio_kg : '';
        $this->peso_total_kg      = $venta->peso_total_kg !== null ? (string) $venta->peso_total_kg : '';
        $this->precio_kg          = $venta->precio_kg !== null ? (string) $venta->precio_kg : '';
        $this->precio_cabeza      = $venta->precio_cabeza !== null ? (string) $venta->precio_cabeza : '';
        $this->importe_total      = (string) $venta->importe_total;
        $this->moneda             = $venta->moneda;
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
        Gate::authorize('ventas.hacienda.registrar');
        $this->recalcular();
        $this->validate();

        $data = [
            'id_establecimiento' => $this->id_establecimiento ?: null,
            'comprador'          => $this->comprador ?: null,
            'corredor_feria'     => $this->corredor_feria ?: null,
            'numero_guia'        => $this->numero_guia ?: null,
            'fecha'              => $this->fecha,
            'tipo_operacion'     => $this->tipo_operacion,
            'categoria'          => $this->categoria,
            'cantidad_cabezas'   => (int) $this->cantidad_cabezas,
            'peso_promedio_kg'   => $this->peso_promedio_kg !== '' ? (float) $this->peso_promedio_kg : null,
            'peso_total_kg'      => $this->peso_total_kg !== '' ? (float) $this->peso_total_kg : null,
            'precio_kg'          => $this->precio_kg !== '' ? (float) $this->precio_kg : null,
            'precio_cabeza'      => $this->precio_cabeza !== '' ? (float) $this->precio_cabeza : null,
            'importe_total'      => (float) $this->importe_total,
            'moneda'             => $this->moneda,
            'estado'             => $this->estado,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            VentaHacienda::findOrFail($this->ventaEditandoId)->update($data);
            session()->flash('success', 'Venta de hacienda actualizada correctamente.');
        } else {
            VentaHacienda::create($data);
            session()->flash('success', 'Venta de hacienda registrada correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function cambiarEstado(string $id, string $estado): void
    {
        if ($estado === 'cobrada') {
            Gate::authorize('ventas.hacienda.aprobar');
        } else {
            Gate::authorize('ventas.hacienda.registrar');
        }

        VentaHacienda::findOrFail($id)->update(['estado' => $estado]);
        session()->flash('success', 'Estado de venta actualizado.');
    }

    private function resetForm(): void
    {
        $this->ventaEditandoId    = null;
        $this->id_establecimiento = '';
        $this->comprador          = '';
        $this->corredor_feria     = '';
        $this->numero_guia        = '';
        $this->fecha              = '';
        $this->tipo_operacion     = 'terminado';
        $this->categoria          = '';
        $this->cantidad_cabezas   = '';
        $this->peso_promedio_kg   = '';
        $this->peso_total_kg      = '';
        $this->precio_kg          = '';
        $this->precio_cabeza      = '';
        $this->importe_total      = '0.00';
        $this->moneda             = 'ARS';
        $this->estado             = 'confirmada';
        $this->observaciones      = '';
        $this->resetValidation();
    }

    public function render()
    {
        $ventas = VentaHacienda::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) =>
                $q->where('comprador', 'like', "%{$this->busqueda}%")
                  ->orWhere('numero_guia', 'like', "%{$this->busqueda}%")
                  ->orWhere('corredor_feria', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroCategoria, fn ($q) => $q->where('categoria', $this->filtroCategoria))
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroFechaDesde, fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta))
            ->with('establecimiento')
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $establecimientos = Establecimiento::orderBy('nombre')->get();

        return view('livewire.ventas.gestion-ventas-hacienda', [
            'ventas'          => $ventas,
            'establecimientos'=> $establecimientos,
            'tiposOperacion'  => VentaHacienda::TIPOS_OPERACION,
            'categorias'      => VentaHacienda::CATEGORIAS,
            'monedas'         => VentaHacienda::MONEDAS,
            'estados'         => VentaHacienda::ESTADOS,
        ]);
    }
}
