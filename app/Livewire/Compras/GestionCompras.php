<?php

namespace App\Livewire\Compras;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Establecimiento;
use App\Models\Insumo;
use App\Models\MovimientoInsumo;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCompras extends Component
{
    use WithPagination;

    public string $busqueda              = '';
    public string $filtroProveedor       = '';
    public string $filtroEstado          = '';
    public string $filtroEstablecimiento = '';
    public string $filtroFechaDesde      = '';
    public string $filtroFechaHasta      = '';

    public bool    $modalAbierto     = false;
    public bool    $modoEdicion      = false;
    public ?string $compraEditandoId = null;

    // Cabecera
    public string $id_proveedor       = '';
    public string $id_establecimiento = '';
    public string $tipo_comprobante   = 'factura_b';
    public string $numero_comprobante = '';
    public string $fecha              = '';
    public string $fecha_vencimiento  = '';
    public string $estado             = 'recibida';
    public string $iva_porc           = '';
    public string $observaciones      = '';

    // Totales (computed)
    public string $subtotal    = '0.00';
    public string $iva_importe = '0.00';
    public string $total       = '0.00';

    // Ítems
    public array $items = [];

    protected function rules(): array
    {
        return [
            'tipo_comprobante'    => 'required|in:' . implode(',', array_keys(Compra::TIPOS_COMPROBANTE)),
            'numero_comprobante'  => 'nullable|string|max:50',
            'id_proveedor'        => 'nullable|exists:proveedores,id',
            'id_establecimiento'  => 'nullable|exists:establecimientos,id',
            'fecha'               => 'required|date',
            'fecha_vencimiento'   => 'nullable|date',
            'estado'              => 'required|in:' . implode(',', array_keys(Compra::ESTADOS)),
            'iva_porc'            => 'nullable|numeric|min:0|max:100',
            'observaciones'       => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.descripcion' => 'required|string|max:200',
            'items.*.cantidad'    => 'required|numeric|min:0.001',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ];
    }

    public function updatedBusqueda(): void              { $this->resetPage(); }
    public function updatedFiltroProveedor(): void       { $this->resetPage(); }
    public function updatedFiltroEstado(): void          { $this->resetPage(); }
    public function updatedFiltroEstablecimiento(): void { $this->resetPage(); }
    public function updatedFiltroFechaDesde(): void      { $this->resetPage(); }
    public function updatedFiltroFechaHasta(): void      { $this->resetPage(); }

    public function updatedItems($value, $key): void
    {
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2) return;
        [$index, $field] = $parts;

        if ($field === 'id_insumo' && !empty($value)) {
            $insumo = Insumo::find($value);
            if ($insumo) {
                if (empty($this->items[$index]['descripcion'])) {
                    $this->items[$index]['descripcion'] = $insumo->nombre;
                }
                $this->items[$index]['unidad'] = $insumo->unidad;
                if ($insumo->precio_referencia && empty($this->items[$index]['precio_unitario'])) {
                    $this->items[$index]['precio_unitario'] = (string) $insumo->precio_referencia;
                }
            }
        }

        if (in_array($field, ['cantidad', 'precio_unitario'])) {
            $cant   = (float) ($this->items[$index]['cantidad'] ?? 0);
            $precio = (float) ($this->items[$index]['precio_unitario'] ?? 0);
            $this->items[$index]['subtotal'] = number_format($cant * $precio, 2, '.', '');
        }

        $this->calcularTotales();
    }

    public function updatedIvaPorc(): void
    {
        $this->calcularTotales();
    }

    public function agregarItem(): void
    {
        $this->items[] = [
            'id_insumo'       => '',
            'descripcion'     => '',
            'cantidad'        => '',
            'unidad'          => '',
            'precio_unitario' => '',
            'subtotal'        => '0.00',
        ];
    }

    public function quitarItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->calcularTotales();
    }

    private function calcularTotales(): void
    {
        $sub = collect($this->items)->sum(fn ($i) => (float) ($i['subtotal'] ?? 0));
        $this->subtotal = number_format($sub, 2, '.', '');

        $iva = 0.0;
        if ($this->iva_porc !== '' && (float) $this->iva_porc > 0) {
            $iva = $sub * (float) $this->iva_porc / 100;
        }
        $this->iva_importe = number_format($iva, 2, '.', '');
        $this->total       = number_format($sub + $iva, 2, '.', '');
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('compras.crear');
        $this->resetForm();
        $this->fecha       = now()->format('Y-m-d');
        $this->modoEdicion = false;
        $this->agregarItem();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('compras.editar');
        $compra = Compra::with('items')->findOrFail($id);
        $this->compraEditandoId    = $id;
        $this->id_proveedor        = $compra->id_proveedor ?? '';
        $this->id_establecimiento  = $compra->id_establecimiento ?? '';
        $this->tipo_comprobante    = $compra->tipo_comprobante;
        $this->numero_comprobante  = $compra->numero_comprobante ?? '';
        $this->fecha               = $compra->fecha->format('Y-m-d');
        $this->fecha_vencimiento   = $compra->fecha_vencimiento?->format('Y-m-d') ?? '';
        $this->estado              = $compra->estado;
        $this->iva_porc            = $compra->iva_porc !== null ? (string) $compra->iva_porc : '';
        $this->observaciones       = $compra->observaciones ?? '';
        $this->subtotal            = (string) $compra->subtotal;
        $this->iva_importe         = $compra->iva_importe !== null ? (string) $compra->iva_importe : '0.00';
        $this->total               = (string) $compra->total;

        $this->items = $compra->items->map(fn ($item) => [
            'id_insumo'       => $item->id_insumo ?? '',
            'descripcion'     => $item->descripcion,
            'cantidad'        => (string) $item->cantidad,
            'unidad'          => $item->unidad ?? '',
            'precio_unitario' => (string) $item->precio_unitario,
            'subtotal'        => (string) $item->subtotal,
        ])->toArray();

        if (empty($this->items)) {
            $this->agregarItem();
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
        Gate::authorize($this->modoEdicion ? 'compras.editar' : 'compras.crear');
        $this->calcularTotales();
        $this->validate();

        $headerData = [
            'id_proveedor'       => $this->id_proveedor ?: null,
            'id_establecimiento' => $this->id_establecimiento ?: null,
            'tipo_comprobante'   => $this->tipo_comprobante,
            'numero_comprobante' => $this->numero_comprobante ?: null,
            'fecha'              => $this->fecha,
            'fecha_vencimiento'  => $this->fecha_vencimiento ?: null,
            'estado'             => $this->estado,
            'subtotal'           => (float) $this->subtotal,
            'iva_porc'           => $this->iva_porc !== '' ? (float) $this->iva_porc : null,
            'iva_importe'        => $this->iva_importe !== '' ? (float) $this->iva_importe : null,
            'total'              => (float) $this->total,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            $compra = Compra::findOrFail($this->compraEditandoId);
            // If already registered in stock, preserve that flag
            $compra->update($headerData);
            $compra->items()->delete();
            session()->flash('success', 'Compra actualizada correctamente.');
        } else {
            $compra = Compra::create($headerData);
            session()->flash('success', 'Compra registrada correctamente.');
        }

        foreach ($this->items as $item) {
            if (empty($item['descripcion']) && empty($item['id_insumo'])) continue;
            $compra->items()->create([
                'id_insumo'       => $item['id_insumo'] ?: null,
                'descripcion'     => $item['descripcion'] ?: '',
                'cantidad'        => (float) $item['cantidad'],
                'unidad'          => $item['unidad'] ?: null,
                'precio_unitario' => (float) $item['precio_unitario'],
                'subtotal'        => (float) $item['subtotal'],
            ]);
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function cambiarEstado(string $id, string $estado): void
    {
        Gate::authorize('compras.editar');
        Compra::findOrFail($id)->update(['estado' => $estado]);
        session()->flash('success', 'Estado de compra actualizado.');
    }

    public function registrarEnStock(string $id): void
    {
        Gate::authorize('compras.editar');
        $compra = Compra::with('items', 'proveedor')->findOrFail($id);

        if ($compra->stock_registrado) {
            session()->flash('error', 'El stock de esta compra ya fue registrado.');
            return;
        }

        $registrados = 0;
        foreach ($compra->items as $item) {
            if (!$item->id_insumo) continue;

            MovimientoInsumo::create([
                'id_insumo'          => $item->id_insumo,
                'id_establecimiento' => $compra->id_establecimiento,
                'tipo'               => 'entrada',
                'motivo'             => 'compra',
                'cantidad'           => $item->cantidad,
                'precio_unitario'    => $item->precio_unitario,
                'importe_total'      => $item->subtotal,
                'fecha'              => $compra->fecha,
                'numero_remito'      => $compra->numero_comprobante,
                'proveedor'          => $compra->proveedor?->nombre,
                'observaciones'      => "Registrado desde compra {$compra->tipo_comprobante_label} {$compra->numero_comprobante}",
            ]);
            $registrados++;
        }

        if ($registrados === 0) {
            session()->flash('error', 'Ningún ítem tiene insumo vinculado al catálogo. Stock no registrado.');
            return;
        }

        $compra->update(['stock_registrado' => true]);
        session()->flash('success', "{$registrados} ítem(s) registrado(s) en stock correctamente.");
    }

    private function resetForm(): void
    {
        $this->compraEditandoId   = null;
        $this->id_proveedor       = '';
        $this->id_establecimiento = '';
        $this->tipo_comprobante   = 'factura_b';
        $this->numero_comprobante = '';
        $this->fecha              = '';
        $this->fecha_vencimiento  = '';
        $this->estado             = 'recibida';
        $this->iva_porc           = '';
        $this->observaciones      = '';
        $this->subtotal           = '0.00';
        $this->iva_importe        = '0.00';
        $this->total              = '0.00';
        $this->items              = [];
        $this->resetValidation();
    }

    public function render()
    {
        $compras = Compra::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) =>
                $q->where('numero_comprobante', 'like', "%{$this->busqueda}%")
                  ->orWhereHas('proveedor', fn ($q) =>
                      $q->where('nombre', 'like', "%{$this->busqueda}%")
                  )
            ))
            ->when($this->filtroProveedor, fn ($q) => $q->where('id_proveedor', $this->filtroProveedor))
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroEstablecimiento, fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroFechaDesde, fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta))
            ->with(['proveedor', 'establecimiento'])
            ->withCount('items')
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $proveedoresOpciones  = Proveedor::activos()->orderBy('nombre')->get();
        $establecimientos     = Establecimiento::orderBy('nombre')->get();
        $insumosOpciones      = Insumo::activos()->orderBy('nombre')->get();

        return view('livewire.compras.gestion-compras', [
            'compras'             => $compras,
            'proveedoresOpciones' => $proveedoresOpciones,
            'establecimientos'    => $establecimientos,
            'insumosOpciones'     => $insumosOpciones,
            'tiposComprobante'    => Compra::TIPOS_COMPROBANTE,
            'estados'             => Compra::ESTADOS,
        ]);
    }
}
