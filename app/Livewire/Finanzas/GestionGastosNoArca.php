<?php

namespace App\Livewire\Finanzas;

use App\Models\GastoNoArca;
use App\Models\Inmueble;
use App\Models\PagoGastoNoArca;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

class GestionGastosNoArca extends Component
{
    // ── Vista ────────────────────────────────────────────────────────────────
    public string $vistaActiva  = 'pagos';   // pagos | catalogo
    #[Url]
    public string $mesSeleccionado = '';     // YYYY-MM

    // ── Pagos del mes (grilla inline) ────────────────────────────────────────
    // [gasto_id => ['importe' => '', 'fecha_pago' => '', 'notas' => '']]
    public array $pagos = [];
    public bool  $hayDirtyCambios = false;

    // ── Filtros catálogo ─────────────────────────────────────────────────────
    public string $filtroCategoria = '';
    public string $filtroEstado    = '';     // '' | activos | inactivos
    public string $busqueda        = '';

    // ── Modal servicio ───────────────────────────────────────────────────────
    public bool    $modalAbierto = false;
    public bool    $modoEdicion  = false;
    public ?string $editandoId   = null;
    public string  $nombre       = '';
    public string  $categoria    = 'otros';
    public string  $id_inmueble  = '';
    public bool    $activo       = true;
    public string  $orden        = '0';

    public function mount(): void
    {
        $this->mesSeleccionado = $this->mesSeleccionado ?: now()->format('Y-m');
        $this->cargarPagos();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Navegación de mes
    // ─────────────────────────────────────────────────────────────────────────
    public function mesPrevio(): void
    {
        $this->mesSeleccionado = Carbon::parse($this->mesSeleccionado . '-01')
            ->subMonth()->format('Y-m');
        $this->hayDirtyCambios = false;
        $this->cargarPagos();
    }

    public function meSiguiente(): void
    {
        $this->mesSeleccionado = Carbon::parse($this->mesSeleccionado . '-01')
            ->addMonth()->format('Y-m');
        $this->hayDirtyCambios = false;
        $this->cargarPagos();
    }

    public function updatedMesSeleccionado(): void
    {
        $this->hayDirtyCambios = false;
        $this->cargarPagos();
    }

    /**
     * Servicios que se muestran en la grilla de un mes: los vigentes (activos)
     * más los dados de baja que igual tengan un pago cargado en ese mes, para
     * que el historial de meses anteriores no desaparezca al dar de baja.
     */
    private function gastosDelMes(string $mesDate): \Illuminate\Support\Collection
    {
        $idsConPago = PagoGastoNoArca::where('mes', $mesDate)->pluck('id_gasto');

        return GastoNoArca::where(function ($q) use ($idsConPago) {
                $q->where('activo', true)->orWhereIn('id', $idsConPago);
            })
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    private function cargarPagos(): void
    {
        $mesDate = $this->mesSeleccionado . '-01';
        $gastos  = $this->gastosDelMes($mesDate);

        $pagosDb = PagoGastoNoArca::where('mes', $mesDate)
            ->whereIn('id_gasto', $gastos->pluck('id'))
            ->get()->keyBy('id_gasto');

        $this->pagos = [];
        foreach ($gastos as $gasto) {
            $pago = $pagosDb->get($gasto->id);
            $this->pagos[$gasto->id] = [
                'importe'    => $pago ? number_format((float) $pago->importe, 2, '.', '') : '',
                'fecha_pago' => $pago?->fecha_pago?->format('Y-m-d') ?? '',
                'notas'      => $pago?->notas ?? '',
            ];
        }
    }

    public function updatedPagos(): void
    {
        $this->hayDirtyCambios = true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar mes
    // ─────────────────────────────────────────────────────────────────────────
    public function guardarMes(): void
    {
        Gate::authorize('finanzas.gastos.gestionar');

        $mesDate = $this->mesSeleccionado . '-01';

        foreach ($this->pagos as $gastoId => $datos) {
            $importeStr = trim($datos['importe'] ?? '');
            $importe    = $this->parsearImporte($importeStr);
            $fechaPago  = $datos['fecha_pago'] ?: null;
            $notas      = trim($datos['notas'] ?? '') ?: null;

            if ($importe === null && !$fechaPago && !$notas) {
                // Sin datos → borrar si existe
                PagoGastoNoArca::where('id_gasto', $gastoId)->where('mes', $mesDate)->delete();
                continue;
            }

            PagoGastoNoArca::updateOrCreate(
                ['id_gasto' => $gastoId, 'mes' => $mesDate],
                [
                    'importe'    => $importe ?? 0,
                    'fecha_pago' => $fechaPago,
                    'notas'      => $notas,
                ]
            );
        }

        $this->hayDirtyCambios = false;
        $this->cargarPagos();
        session()->flash('success', 'Pagos guardados para ' . $this->formatearMes($this->mesSeleccionado) . '.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Catálogo de servicios
    // ─────────────────────────────────────────────────────────────────────────
    public function abrirModalCrear(): void
    {
        Gate::authorize('finanzas.gastos.gestionar');
        $this->limpiarModal();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('finanzas.gastos.gestionar');
        $gasto = GastoNoArca::findOrFail($id);

        $this->editandoId  = $id;
        $this->modoEdicion = true;
        $this->nombre      = $gasto->nombre;
        $this->categoria   = $gasto->categoria;
        $this->id_inmueble = $gasto->id_inmueble ?? '';
        $this->activo      = $gasto->activo;
        $this->orden       = (string) $gasto->orden;
        $this->modalAbierto = true;
    }

    public function guardarServicio(): void
    {
        Gate::authorize('finanzas.gastos.gestionar');

        $this->validate([
            'nombre'      => 'required|string|max:200',
            'categoria'   => 'required|in:' . implode(',', array_keys(GastoNoArca::CATEGORIAS)),
            'id_inmueble' => 'nullable|exists:inmuebles,id',
            'orden'       => 'nullable|integer|min:0',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
        ]);

        $datos = [
            'nombre'      => $this->nombre,
            'categoria'   => $this->categoria,
            'id_inmueble' => $this->id_inmueble ?: null,
            'activo'      => $this->activo,
            'orden'       => (int) ($this->orden ?: 0),
        ];

        if ($this->modoEdicion) {
            $gasto = GastoNoArca::findOrFail($this->editandoId);
            $gasto->update($datos);
            session()->flash('success', "Servicio \"{$gasto->nombre}\" actualizado.");
        } else {
            $datos['id'] = Str::uuid();
            GastoNoArca::create($datos);
            session()->flash('success', "Servicio \"{$this->nombre}\" creado.");
        }

        $this->cerrarModal();
        if ($this->vistaActiva === 'pagos') {
            $this->cargarPagos();
        }
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('finanzas.gastos.gestionar');
        $gasto = GastoNoArca::findOrFail($id);
        $gasto->update(['activo' => !$gasto->activo]);

        session()->flash('success', $gasto->activo
            ? "Servicio \"{$gasto->nombre}\" reactivado."
            : "Servicio \"{$gasto->nombre}\" dado de baja. Los pagos ya cargados se conservan.");

        $this->cargarPagos();
    }

    /**
     * Baja desde la grilla de pagos: el servicio deja de aparecer en los meses
     * en los que no tenga nada cargado, pero el historial queda intacto.
     */
    public function darDeBaja(string $id): void
    {
        Gate::authorize('finanzas.gastos.gestionar');
        $gasto = GastoNoArca::findOrFail($id);
        $gasto->update(['activo' => false]);

        $this->cargarPagos();
        session()->flash('success', "\"{$gasto->nombre}\" ya no se paga más: se dio de baja. "
            . 'Los pagos históricos se conservan y podés reactivarlo desde "Catálogo de servicios".');
    }

    /**
     * Borrado definitivo. Sólo se permite si nunca se le cargó un pago; si tiene
     * historial se ofrece la baja, que no destruye datos.
     */
    public function eliminarServicio(string $id): void
    {
        Gate::authorize('finanzas.gastos.gestionar');
        $gasto = GastoNoArca::withCount('pagos')->findOrFail($id);

        if ($gasto->pagos_count > 0) {
            session()->flash('error', "No se puede eliminar \"{$gasto->nombre}\": tiene {$gasto->pagos_count} "
                . 'pago(s) registrados. Dalo de baja para que deje de aparecer sin perder el historial.');
            return;
        }

        $nombre = $gasto->nombre;
        $gasto->delete();

        $this->cargarPagos();
        session()->flash('success', "Servicio \"{$nombre}\" eliminado.");
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarModal();
    }

    private function limpiarModal(): void
    {
        $this->reset(['editandoId', 'modoEdicion', 'nombre', 'id_inmueble']);
        $this->categoria = 'otros';
        $this->activo    = true;
        $this->orden     = '0';
        $this->resetValidation();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render(): \Illuminate\View\View
    {
        // Gastos para la grilla de pagos (vigentes + bajas con pago en el mes),
        // agrupados por categoría para que cada rubro tenga un solo encabezado
        // y un solo subtotal aunque el "orden" no venga contiguo.
        $gastosGrilla = $this->gastosDelMes($this->mesSeleccionado . '-01')
            ->groupBy('categoria');

        // Total del mes
        $totalMes = collect($this->pagos)
            ->sum(fn ($p) => $this->parsearImporte($p['importe'] ?? '') ?? 0);

        // Servicios para el catálogo
        $catalogo = GastoNoArca::query()
            ->with('inmueble')
            ->withCount('pagos')
            ->when($this->filtroCategoria, fn ($q) => $q->where('categoria', $this->filtroCategoria))
            ->when($this->filtroEstado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($this->filtroEstado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->when($this->busqueda, fn ($q) => $q->where('nombre', 'like', "%{$this->busqueda}%"))
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('livewire.finanzas.gestion-gastos-no-arca', [
            'gastosGrilla'  => $gastosGrilla,
            'totalMes'      => $totalMes,
            'catalogo'      => $catalogo,
            'totalCatalogo' => GastoNoArca::count(),
            'categorias'    => GastoNoArca::CATEGORIAS,
            'inmuebles'     => Inmueble::where('activo', true)->orderBy('nombre')->get(),
            'mesLabel'      => $this->formatearMes($this->mesSeleccionado),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function parsearImporte(string $valor): ?float
    {
        $valor = trim($valor);
        if ($valor === '' || $valor === '-') return null;
        // Acepta formato argentino (punto=miles, coma=decimal) y estándar
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $valor)) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } else {
            $valor = str_replace(',', '.', str_replace(' ', '', $valor));
        }
        return is_numeric($valor) ? (float) $valor : null;
    }

    private function formatearMes(string $ym): string
    {
        try {
            return Carbon::parse($ym . '-01')->locale('es')->isoFormat('MMMM [de] YYYY');
        } catch (\Exception) {
            return $ym;
        }
    }
}
