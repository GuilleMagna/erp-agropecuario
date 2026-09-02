<?php

namespace App\Livewire\Ventas;

use App\Models\Campana;
use App\Models\CategoriaVenta;
use App\Models\Comprador;
use App\Models\Establecimiento;
use App\Models\VentaGrano;
use App\Traits\CambiaEmpresaDesdeQuery;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class GestionVentasGranos extends Component
{
    use CambiaEmpresaDesdeQuery, WithPagination;

    public string $busqueda = '';

    public string $filtroCereal = '';

    public string $filtroEstado = '';

    #[Url]
    public string $filtroFechaDesde = '';

    #[Url]
    public string $filtroFechaHasta = '';

    /**
     * Deja sólo las liquidaciones que sufrieron retención de IVA. Lo usa el
     * link de "Retenciones" del reporte de IVA: sin esto abría el listado
     * completo del mes, con ventas que no aportan nada a ese número.
     */
    #[Url]
    public bool $filtroConRetencion = false;

    public function mount(): void
    {
        $this->switchEmpresaDesdeQuery();
    }

    /** Vuelve al listado completo: los links del reporte de IVA llegan con
     *  varios filtros puestos a la vez. */
    public function limpiarFiltros(): void
    {
        $this->reset([
            'busqueda', 'filtroCereal', 'filtroEstado',
            'filtroFechaDesde', 'filtroFechaHasta', 'filtroConRetencion',
        ]);
        $this->resetPage();
    }

    public function hayFiltros(): bool
    {
        return $this->busqueda !== '' || $this->filtroCereal !== '' || $this->filtroEstado !== ''
            || $this->filtroFechaDesde !== '' || $this->filtroFechaHasta !== '' || $this->filtroConRetencion;
    }

    public bool $modalAbierto = false;

    public bool $modoEdicion = false;

    public ?string $ventaEditandoId = null;

    public string $id_establecimiento = '';

    public string $id_campana = '';

    public string $comprador = '';

    public string $cuit_comprador = '';

    public string $cereal = '';

    public string $tipo_venta = 'disponible';

    public string $corredor = '';

    public string $numero_comprobante = '';

    public string $fecha = '';

    public string $fecha_entrega = '';

    public string $moneda = 'ARS';

    public string $estado = 'confirmada';

    public string $observaciones = '';

    // Campos que replican la hoja VENTAS del Excel de control mensual.
    // cantidad_kg es el valor canónico (siempre en KG) que se valida y guarda.
    // cantidadIngresada + unidadCantidad son lo que el usuario ve y tipea: puede
    // cargar en KG o en Quintales (1 quintal = 100kg) según cómo lo tenga a mano.
    public string $cantidad_kg = '';

    public string $cantidadIngresada = '';

    public string $unidadCantidad = 'kg';

    public string $factor = '100';

    public string $precio_kg = '';

    /** Flete ingresado como figura habitualmente en la liquidación: por tonelada. */
    public string $flete_tn = '0';

    public string $deducciones = '0';

    public string $iva_deducciones = '0';

    public string $bonificacion = '0';

    public string $ret_ganancias = '0';

    public string $ret_iva = '0';

    public string $iva_rg4310 = '0';

    protected function rules(): array
    {
        return [
            'id_establecimiento' => 'nullable|exists:establecimientos,id',
            'id_campana' => 'nullable|exists:campanas,id',
            'comprador' => 'nullable|string|max:200',
            'cuit_comprador' => 'nullable|string|max:20',
            'cereal' => 'required|in:'.implode(',', array_keys(VentaGrano::CEREALES)),
            'tipo_venta' => 'required|in:'.implode(',', array_keys(VentaGrano::TIPOS_VENTA)),
            'corredor' => 'nullable|string|max:150',
            'numero_comprobante' => 'nullable|string|max:50',
            'fecha' => 'required|date',
            'fecha_entrega' => 'nullable|date',
            'cantidadIngresada' => 'required|numeric|min:0.01',
            'unidadCantidad' => 'required|in:kg,quintales,tn',
            'factor' => 'required|numeric|min:0.01',
            'precio_kg' => 'required|numeric|min:0',
            'flete_tn' => 'nullable|numeric|min:0',
            'deducciones' => 'nullable|numeric',
            'iva_deducciones' => 'nullable|numeric',
            'bonificacion' => 'nullable|numeric',
            'ret_ganancias' => 'nullable|numeric',
            'ret_iva' => 'nullable|numeric',
            'iva_rg4310' => 'nullable|numeric',
            'moneda' => 'required|in:'.implode(',', array_keys(VentaGrano::MONEDAS)),
            'estado' => 'required|in:'.implode(',', array_keys(VentaGrano::ESTADOS)),
            'observaciones' => 'nullable|string',
        ];
    }

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroCereal(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaHasta(): void
    {
        $this->resetPage();
    }

    public function updatedCantidadIngresada(): void
    {
        $this->recalcularCantidadKg();
    }

    public function updatedUnidadCantidad(): void
    {
        // cantidad_kg ya tiene la cantidad física correcta (calculada con la
        // unidad anterior); solo hay que re-expresar el número mostrado en la
        // nueva unidad, no reinterpretarlo. Livewire ya actualizó
        // unidadCantidad para este momento, por eso no se recalcula cantidad_kg
        // acá: solo se regenera cantidadIngresada a partir de cantidad_kg.
        if ($this->cantidad_kg !== '') {
            $kg = (float) $this->cantidad_kg;
            $cantidad = match ($this->unidadCantidad) {
                'quintales' => $kg / 100,
                'tn' => $kg / 1000,
                default => $kg,
            };

            $this->cantidadIngresada = (string) round($cantidad, 4);
        }
    }

    private function recalcularCantidadKg(): void
    {
        if ($this->cantidadIngresada === '') {
            $this->cantidad_kg = '';

            return;
        }

        $multiplicador = match ($this->unidadCantidad) {
            'quintales' => 100,
            'tn' => 1000,
            default => 1,
        };

        $kg = (float) $this->cantidadIngresada * $multiplicador;

        $this->cantidad_kg = (string) round($kg, 2);
    }

    /**
     * Réplica de la columna "Subtotal" de la hoja VENTAS del Excel:
     * =+Cantidad*Factor/100*(Precio-Flete)
     */
    public function subtotalCalculado(): float
    {
        if ($this->cantidad_kg === '' || $this->precio_kg === '') {
            return 0.0;
        }

        $factor = (float) ($this->factor !== '' ? $this->factor : 100);

        $fleteKg = (float) ($this->flete_tn ?: 0) / 1000;

        return (float) $this->cantidad_kg * ($factor / 100) * ((float) $this->precio_kg - $fleteKg);
    }

    /** Réplica de "Total Reten. AFIP": =+Ret.Gan.+Ret.IVA */
    public function totalRetencionesAfipCalculado(): float
    {
        return (float) ($this->ret_ganancias ?: 0) + (float) ($this->ret_iva ?: 0);
    }

    /** Réplica de "Resultado IVA": =+Subtotal*0.105-IVA_deducciones */
    public function resultadoIvaCalculado(): float
    {
        return $this->subtotalCalculado() * 0.105 - (float) ($this->iva_deducciones ?: 0);
    }

    /**
     * Réplica de la rama de granos de la fórmula "Total" del Excel:
     * =+Subtotal + Ret.IVA + IVA_RG_4310 - DEDUCCIONES - IVA_deducciones + BONIF.
     */
    public function totalCalculado(): float
    {
        return $this->subtotalCalculado()
            + (float) ($this->ret_iva ?: 0)
            + (float) ($this->iva_rg4310 ?: 0)
            - (float) ($this->deducciones ?: 0)
            - (float) ($this->iva_deducciones ?: 0)
            + (float) ($this->bonificacion ?: 0);
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('ventas.granos.registrar');
        $this->resetForm();
        $this->fecha = now()->format('Y-m-d');
        $this->modoEdicion = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('ventas.granos.registrar');
        $venta = VentaGrano::findOrFail($id);
        $this->ventaEditandoId = $id;
        $this->id_establecimiento = $venta->id_establecimiento ?? '';
        $this->id_campana = $venta->id_campana ?? '';
        $this->comprador = $venta->comprador ?? '';
        $this->cuit_comprador = $venta->cuit_comprador ?? '';
        $this->cereal = $venta->cereal;
        $this->tipo_venta = $venta->tipo_venta;
        $this->corredor = $venta->corredor ?? '';
        $this->numero_comprobante = $venta->numero_comprobante ?? '';
        $this->fecha = $venta->fecha->format('Y-m-d');
        $this->fecha_entrega = $venta->fecha_entrega?->format('Y-m-d') ?? '';
        $this->moneda = $venta->moneda;
        $this->estado = $venta->estado;
        $this->observaciones = $venta->observaciones ?? '';

        // Ventas cargadas antes de este cambio (o importadas del Excel histórico)
        // no tienen el detalle de liquidación: se estima cantidad/precio en kg a
        // partir de lo guardado en tn para que el formulario no arranque en blanco.
        $this->cantidad_kg = (string) ($venta->cantidad_kg ?? round((float) $venta->cantidad_tn * 1000, 2));
        $this->unidadCantidad = 'kg';
        $this->cantidadIngresada = $this->cantidad_kg;
        $this->precio_kg = (string) ($venta->precio_kg ?? round((float) $venta->precio_tn / 1000, 4));
        $this->factor = (string) ($venta->factor ?? '100');
        // La base conserva el flete por kg por compatibilidad histórica, pero
        // el formulario lo presenta por tonelada tal como viene en la liquidación.
        $this->flete_tn = (string) round((float) ($venta->flete_kg ?? 0) * 1000, 4);
        $this->deducciones = (string) ($venta->deducciones ?? '0');
        $this->iva_deducciones = (string) ($venta->iva_deducciones ?? '0');
        $this->bonificacion = (string) ($venta->bonificacion ?? '0');
        $this->ret_ganancias = (string) ($venta->ret_ganancias ?? '0');
        $this->ret_iva = (string) ($venta->ret_iva ?? '0');
        $this->iva_rg4310 = (string) ($venta->iva_rg4310 ?? '0');

        $this->modoEdicion = true;
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function guardar(): void
    {
        Gate::authorize('ventas.granos.registrar');
        $this->recalcularCantidadKg();
        $this->validate();

        $idComprador = null;
        if ($this->comprador !== '') {
            $categoriaCereales = CategoriaVenta::where('tipo_cantidad', 'quintales')->first();
            $idComprador = Comprador::firstOrCreateParaCategoria($this->comprador, $categoriaCereales?->id)->id;
        }

        $data = [
            'id_establecimiento' => $this->id_establecimiento ?: null,
            'id_campana' => $this->id_campana ?: null,
            'comprador' => $this->comprador ?: null,
            'id_comprador' => $idComprador,
            'cuit_comprador' => $this->cuit_comprador ?: null,
            'cereal' => $this->cereal,
            'tipo_venta' => $this->tipo_venta,
            'corredor' => $this->corredor ?: null,
            'numero_comprobante' => $this->numero_comprobante ?: null,
            'fecha' => $this->fecha,
            'fecha_entrega' => $this->fecha_entrega ?: null,
            // cantidad_tn/precio_tn se mantienen en tn (usados en el listado, el
            // dashboard y los reportes fiscal/económico) derivados de los campos en
            // kg, que son los que se cargan según la hoja VENTAS del Excel.
            'cantidad_tn' => round((float) $this->cantidad_kg / 1000, 3),
            'precio_tn' => round((float) $this->precio_kg * 1000, 2),
            'moneda' => $this->moneda,
            'importe_total' => round($this->totalCalculado(), 2),
            'cantidad_kg' => (float) $this->cantidad_kg,
            'factor' => (float) $this->factor,
            'precio_kg' => (float) $this->precio_kg,
            'flete_kg' => (float) ($this->flete_tn ?: 0) / 1000,
            'deducciones' => (float) ($this->deducciones ?: 0),
            'iva_deducciones' => (float) ($this->iva_deducciones ?: 0),
            'bonificacion' => (float) ($this->bonificacion ?: 0),
            'ret_ganancias' => (float) ($this->ret_ganancias ?: 0),
            'ret_iva' => (float) ($this->ret_iva ?: 0),
            'iva_rg4310' => (float) ($this->iva_rg4310 ?: 0),
            'estado' => $this->estado,
            'observaciones' => $this->observaciones ?: null,
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
        $this->ventaEditandoId = null;
        $this->id_establecimiento = '';
        $this->id_campana = '';
        $this->comprador = '';
        $this->cuit_comprador = '';
        $this->cereal = '';
        $this->tipo_venta = 'disponible';
        $this->corredor = '';
        $this->numero_comprobante = '';
        $this->fecha = '';
        $this->fecha_entrega = '';
        $this->moneda = 'ARS';
        $this->estado = 'confirmada';
        $this->observaciones = '';
        $this->cantidad_kg = '';
        $this->cantidadIngresada = '';
        $this->unidadCantidad = 'kg';
        $this->factor = '100';
        $this->precio_kg = '';
        $this->flete_tn = '0';
        $this->deducciones = '0';
        $this->iva_deducciones = '0';
        $this->bonificacion = '0';
        $this->ret_ganancias = '0';
        $this->ret_iva = '0';
        $this->iva_rg4310 = '0';
        $this->resetValidation();
    }

    public function render()
    {
        $ventas = VentaGrano::query()
            ->when($this->busqueda, fn ($q) => $q->where(fn ($q) => $q->where('comprador', 'like', "%{$this->busqueda}%")
                ->orWhere('numero_comprobante', 'like', "%{$this->busqueda}%")
                ->orWhere('corredor', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroCereal, fn ($q) => $q->where('cereal', $this->filtroCereal))
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroFechaDesde, fn ($q) => $q->where('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn ($q) => $q->where('fecha', '<=', $this->filtroFechaHasta))
            ->when($this->filtroConRetencion, fn ($q) => $q->where('ret_iva', '!=', 0))
            ->with(['establecimiento', 'campana'])
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $establecimientos = Establecimiento::orderBy('nombre')->get();
        $campanas = Campana::activos()->orderBy('nombre')->get();

        $compradoresSugeridos = Comprador::activos()
            ->whereHas('categoriaVenta', fn ($q) => $q->where('tipo_cantidad', 'quintales'))
            ->orderBy('nombre')
            ->pluck('nombre');

        return view('livewire.ventas.gestion-ventas-granos', [
            'ventas' => $ventas,
            'establecimientos' => $establecimientos,
            'campanas' => $campanas,
            'compradoresSugeridos' => $compradoresSugeridos,
            'cereales' => VentaGrano::CEREALES,
            'tiposVenta' => VentaGrano::TIPOS_VENTA,
            'monedas' => VentaGrano::MONEDAS,
            'estados' => VentaGrano::ESTADOS,
        ]);
    }
}
