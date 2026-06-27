<?php

namespace App\Livewire\Compras;

use App\Models\Campana;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Establecimiento;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\MovimientoInsumo;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCompras extends Component
{
    use WithPagination;

    // Filtros
    public string $busqueda              = '';
    public string $filtroProveedor       = '';
    public string $filtroEstado          = '';
    public string $filtroActividad       = '';
    public string $filtroEstablecimiento = '';
    public string $filtroFechaDesde      = '';
    public string $filtroFechaHasta      = '';

    // Modal individual
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

    // Imputación
    public string $actividad  = 'general';
    public string $id_lote    = '';
    public string $id_campana = '';

    // Totales
    public string $subtotal    = '0.00';
    public string $iva_importe = '0.00';
    public string $total       = '0.00';

    // Ítems
    public array $items = [];

    // ── Selección masiva ────────────────────────────────────────
    public array  $seleccionados      = [];
    public bool   $seleccionarTodos   = false;
    public bool   $modalMasivoAbierto = false;
    public string $accionMasiva       = 'estado';      // 'estado' | 'actividad' | 'imputacion'
    public string $valorMasivoEstado  = '';
    public string $valorMasivoActividad = '';
    public string $lotesMasivo        = '';
    public string $campanaMasivo      = '';

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
            'actividad'           => 'nullable|in:' . implode(',', array_keys(Compra::ACTIVIDADES)),
            'id_lote'             => 'nullable|exists:lotes,id',
            'id_campana'          => 'nullable|exists:campanas,id',
            'iva_porc'            => 'nullable|numeric|min:0|max:100',
            'observaciones'       => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.descripcion' => 'required|string|max:200',
            'items.*.cantidad'    => 'required|numeric|min:0.001',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ];
    }

    public function updatedBusqueda(): void              { $this->resetPage(); $this->seleccionados = []; }
    public function updatedFiltroProveedor(): void       { $this->resetPage(); $this->seleccionados = []; }
    public function updatedFiltroEstado(): void          { $this->resetPage(); $this->seleccionados = []; }
    public function updatedFiltroActividad(): void       { $this->resetPage(); $this->seleccionados = []; }
    public function updatedFiltroEstablecimiento(): void { $this->resetPage(); $this->seleccionados = []; }
    public function updatedFiltroFechaDesde(): void      { $this->resetPage(); $this->seleccionados = []; }
    public function updatedFiltroFechaHasta(): void      { $this->resetPage(); $this->seleccionados = []; }

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

    public function updatedIvaPorc(): void { $this->calcularTotales(); }

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

    // ── CRUD individual ─────────────────────────────────────────

    public function abrirModalCrear(): void
    {
        Gate::authorize('compras.crear');
        $this->resetForm();
        $this->fecha        = now()->format('Y-m-d');
        $this->modoEdicion  = false;
        $this->agregarItem();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('compras.editar');
        $compra = Compra::with('items')->findOrFail($id);
        $this->compraEditandoId   = $id;
        $this->id_proveedor       = $compra->id_proveedor ?? '';
        $this->id_establecimiento = $compra->id_establecimiento ?? '';
        $this->tipo_comprobante   = $compra->tipo_comprobante;
        $this->numero_comprobante = $compra->numero_comprobante ?? '';
        $this->fecha              = $compra->fecha->format('Y-m-d');
        $this->fecha_vencimiento  = $compra->fecha_vencimiento?->format('Y-m-d') ?? '';
        $this->estado             = $compra->estado;
        $this->actividad          = $compra->actividad ?? 'general';
        $this->id_lote            = $compra->id_lote ?? '';
        $this->id_campana         = $compra->id_campana ?? '';
        $this->iva_porc           = $compra->iva_porc !== null ? (string) $compra->iva_porc : '';
        $this->observaciones      = $compra->observaciones ?? '';
        $this->subtotal           = (string) $compra->subtotal;
        $this->iva_importe        = $compra->iva_importe !== null ? (string) $compra->iva_importe : '0.00';
        $this->total              = (string) $compra->total;

        $this->items = $compra->items->map(fn ($item) => [
            'id_insumo'       => $item->id_insumo ?? '',
            'descripcion'     => $item->descripcion,
            'cantidad'        => (string) $item->cantidad,
            'unidad'          => $item->unidad ?? '',
            'precio_unitario' => (string) $item->precio_unitario,
            'subtotal'        => (string) $item->subtotal,
        ])->toArray();

        if (empty($this->items)) $this->agregarItem();

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

        $data = [
            'id_proveedor'       => $this->id_proveedor ?: null,
            'id_establecimiento' => $this->id_establecimiento ?: null,
            'tipo_comprobante'   => $this->tipo_comprobante,
            'numero_comprobante' => $this->numero_comprobante ?: null,
            'fecha'              => $this->fecha,
            'fecha_vencimiento'  => $this->fecha_vencimiento ?: null,
            'estado'             => $this->estado,
            'actividad'          => $this->actividad ?: 'general',
            'id_lote'            => $this->id_lote ?: null,
            'id_campana'         => $this->id_campana ?: null,
            'subtotal'           => (float) $this->subtotal,
            'iva_porc'           => $this->iva_porc !== '' ? (float) $this->iva_porc : null,
            'iva_importe'        => $this->iva_importe !== '' ? (float) $this->iva_importe : null,
            'total'              => (float) $this->total,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            $compra = Compra::findOrFail($this->compraEditandoId);
            $compra->update($data);
            $compra->items()->delete();
            session()->flash('success', 'Compra actualizada correctamente.');
        } else {
            $compra = Compra::create($data);
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
        session()->flash('success', 'Estado actualizado.');
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
            session()->flash('error', 'Ningún ítem tiene insumo vinculado. Stock no registrado.');
            return;
        }

        $compra->update(['stock_registrado' => true]);
        session()->flash('success', "{$registrados} ítem(s) registrado(s) en stock.");
    }

    // ── Selección y acciones masivas ────────────────────────────

    public function updatedSeleccionarTodos(bool $value): void
    {
        // Se llama después de que el checkbox de "todos" cambia.
        // La lógica real de poblar IDs se hace en toggleTodos() desde la vista.
    }

    public function toggleTodos(array $idsEnPagina): void
    {
        if (count($this->seleccionados) === count($idsEnPagina)
            && empty(array_diff($idsEnPagina, $this->seleccionados))) {
            $this->seleccionados    = [];
            $this->seleccionarTodos = false;
        } else {
            $this->seleccionados    = $idsEnPagina;
            $this->seleccionarTodos = true;
        }
    }

    public function abrirModalMasivo(): void
    {
        Gate::authorize('compras.editar');
        if (empty($this->seleccionados)) return;
        $this->accionMasiva         = 'estado';
        $this->valorMasivoEstado    = '';
        $this->valorMasivoActividad = '';
        $this->lotesMasivo          = '';
        $this->campanaMasivo        = '';
        $this->modalMasivoAbierto   = true;
    }

    public function aplicarMasivo(): void
    {
        Gate::authorize('compras.editar');
        if (empty($this->seleccionados)) return;

        $query = Compra::whereIn('id', $this->seleccionados);
        $count = count($this->seleccionados);

        switch ($this->accionMasiva) {
            case 'estado':
                $this->validate(['valorMasivoEstado' => 'required|in:' . implode(',', array_keys(Compra::ESTADOS))]);
                $query->update(['estado' => $this->valorMasivoEstado]);
                session()->flash('success', "{$count} comprobante(s) actualizados a estado «" . Compra::ESTADOS[$this->valorMasivoEstado] . "».");
                break;

            case 'actividad':
                $this->validate(['valorMasivoActividad' => 'required|in:' . implode(',', array_keys(Compra::ACTIVIDADES))]);
                $query->update(['actividad' => $this->valorMasivoActividad]);
                session()->flash('success', "{$count} comprobante(s) imputados a «" . Compra::ACTIVIDADES[$this->valorMasivoActividad] . "».");
                break;

            case 'imputacion':
                $this->validate([
                    'valorMasivoActividad' => 'required|in:' . implode(',', array_keys(Compra::ACTIVIDADES)),
                    'lotesMasivo'          => 'nullable|exists:lotes,id',
                    'campanaMasivo'        => 'nullable|exists:campanas,id',
                ]);
                $query->update([
                    'actividad'  => $this->valorMasivoActividad,
                    'id_lote'    => $this->lotesMasivo ?: null,
                    'id_campana' => $this->campanaMasivo ?: null,
                ]);
                session()->flash('success', "{$count} comprobante(s) con imputación actualizada.");
                break;
        }

        $this->seleccionados      = [];
        $this->seleccionarTodos   = false;
        $this->modalMasivoAbierto = false;
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados    = [];
        $this->seleccionarTodos = false;
    }

    // ────────────────────────────────────────────────────────────

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
        $this->actividad          = 'general';
        $this->id_lote            = '';
        $this->id_campana         = '';
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
            ->when($this->filtroProveedor,       fn ($q) => $q->where('id_proveedor', $this->filtroProveedor))
            ->when($this->filtroEstado,           fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroActividad,        fn ($q) => $q->where('actividad', $this->filtroActividad))
            ->when($this->filtroEstablecimiento,  fn ($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->when($this->filtroFechaDesde,       fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta,       fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta))
            ->with(['proveedor', 'establecimiento', 'lote', 'campana'])
            ->withCount('items')
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.compras.gestion-compras', [
            'compras'             => $compras,
            'proveedoresOpciones' => Proveedor::activos()->orderBy('nombre')->get(),
            'establecimientos'    => Establecimiento::orderBy('nombre')->get(),
            'insumosOpciones'     => Insumo::activos()->orderBy('nombre')->get(),
            'lotes'               => Lote::orderBy('nombre')->get(),
            'campanas'            => Campana::orderBy('nombre')->get(),
            'tiposComprobante'    => Compra::TIPOS_COMPROBANTE,
            'estados'             => Compra::ESTADOS,
            'actividades'         => Compra::ACTIVIDADES,
        ]);
    }
}
