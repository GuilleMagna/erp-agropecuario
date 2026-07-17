<?php

namespace App\Livewire\Finanzas;

use App\Models\GastoNoArca;
use App\Models\IngresoAlquiler;
use App\Models\Inmueble;
use App\Models\PagoGastoNoArca;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

class GestionInmuebles extends Component
{
    // ── Vista ────────────────────────────────────────────────────────────────
    public string $vistaActiva     = 'ingresos';   // ingresos | catalogo
    #[Url]
    public string $mesSeleccionado = '';           // YYYY-MM

    // ── Ingresos del mes (grilla inline) ────────────────────────────────────
    // [inmueble_id => ['importe' => '', 'fecha_cobro' => '', 'notas' => '']]
    public array $ingresos        = [];
    public bool  $hayDirtyCambios = false;

    // ── Modal inmueble ───────────────────────────────────────────────────────
    public bool    $modalAbierto        = false;
    public bool    $modoEdicion         = false;
    public ?string $inmuebleEditandoId  = null;
    public string  $nombre              = '';
    public string  $localidad           = '';
    public bool    $activo              = true;

    public function mount(): void
    {
        $this->mesSeleccionado = $this->mesSeleccionado ?: now()->format('Y-m');
        $this->cargarIngresos();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Navegación de mes
    // ─────────────────────────────────────────────────────────────────────────
    public function mesPrevio(): void
    {
        $this->mesSeleccionado = Carbon::parse($this->mesSeleccionado . '-01')
            ->subMonth()->format('Y-m');
        $this->hayDirtyCambios = false;
        $this->cargarIngresos();
    }

    public function mesSiguiente(): void
    {
        $this->mesSeleccionado = Carbon::parse($this->mesSeleccionado . '-01')
            ->addMonth()->format('Y-m');
        $this->hayDirtyCambios = false;
        $this->cargarIngresos();
    }

    public function updatedMesSeleccionado(): void
    {
        $this->hayDirtyCambios = false;
        $this->cargarIngresos();
    }

    private function cargarIngresos(): void
    {
        $mesDate   = $this->mesSeleccionado . '-01';
        $inmuebles = Inmueble::where('activo', true)->orderBy('nombre')->get();

        $ingresosDb = IngresoAlquiler::where('mes', $mesDate)
            ->whereIn('id_inmueble', $inmuebles->pluck('id'))
            ->get()->keyBy('id_inmueble');

        $this->ingresos = [];
        foreach ($inmuebles as $inmueble) {
            $ingreso = $ingresosDb->get($inmueble->id);
            $this->ingresos[$inmueble->id] = [
                'importe'     => $ingreso ? number_format((float) $ingreso->importe, 2, '.', '') : '',
                'fecha_cobro' => $ingreso?->fecha_cobro?->format('Y-m-d') ?? '',
                'notas'       => $ingreso?->notas ?? '',
            ];
        }
    }

    public function updatedIngresos(): void
    {
        $this->hayDirtyCambios = true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar mes
    // ─────────────────────────────────────────────────────────────────────────
    public function guardarMes(): void
    {
        Gate::authorize('finanzas.inmuebles.gestionar');

        $mesDate = $this->mesSeleccionado . '-01';

        foreach ($this->ingresos as $inmuebleId => $datos) {
            $importeStr = trim($datos['importe'] ?? '');
            $importe    = $this->parsearImporte($importeStr);
            $fechaCobro = $datos['fecha_cobro'] ?: null;
            $notas      = trim($datos['notas'] ?? '') ?: null;

            if ($importe === null && !$fechaCobro && !$notas) {
                IngresoAlquiler::where('id_inmueble', $inmuebleId)->where('mes', $mesDate)->delete();
                continue;
            }

            IngresoAlquiler::updateOrCreate(
                ['id_inmueble' => $inmuebleId, 'mes' => $mesDate],
                [
                    'importe'     => $importe ?? 0,
                    'fecha_cobro' => $fechaCobro,
                    'notas'       => $notas,
                ]
            );
        }

        $this->hayDirtyCambios = false;
        $this->cargarIngresos();
        session()->flash('success', 'Ingresos guardados para ' . $this->formatearMes($this->mesSeleccionado) . '.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Catálogo de inmuebles
    // ─────────────────────────────────────────────────────────────────────────
    public function abrirModalCrear(): void
    {
        Gate::authorize('finanzas.inmuebles.gestionar');
        $this->limpiarModal();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('finanzas.inmuebles.gestionar');
        $inmueble = Inmueble::findOrFail($id);

        $this->inmuebleEditandoId = $id;
        $this->modoEdicion        = true;
        $this->nombre             = $inmueble->nombre;
        $this->localidad          = $inmueble->localidad ?? '';
        $this->activo             = $inmueble->activo;
        $this->modalAbierto       = true;
    }

    public function guardarInmueble(): void
    {
        Gate::authorize('finanzas.inmuebles.gestionar');

        $this->validate([
            'nombre'    => 'required|string|max:150',
            'localidad' => 'nullable|string|max:100',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
        ]);

        $datos = [
            'nombre'    => $this->nombre,
            'localidad' => $this->localidad ?: null,
            'activo'    => $this->activo,
        ];

        if ($this->modoEdicion) {
            $inmueble = Inmueble::findOrFail($this->inmuebleEditandoId);
            $inmueble->update($datos);
            session()->flash('success', "Inmueble \"{$inmueble->nombre}\" actualizado.");
        } else {
            Inmueble::create($datos);
            session()->flash('success', "Inmueble \"{$this->nombre}\" creado.");
        }

        $this->cerrarModal();
        if ($this->vistaActiva === 'ingresos') {
            $this->cargarIngresos();
        }
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('finanzas.inmuebles.gestionar');
        $inmueble = Inmueble::findOrFail($id);
        $inmueble->update(['activo' => !$inmueble->activo]);
        if ($this->vistaActiva === 'ingresos') {
            $this->cargarIngresos();
        }
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarModal();
    }

    private function limpiarModal(): void
    {
        $this->reset(['inmuebleEditandoId', 'modoEdicion', 'nombre', 'localidad']);
        $this->activo = true;
        $this->resetValidation();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────
    public function render(): \Illuminate\View\View
    {
        $inmueblesActivos = Inmueble::where('activo', true)->orderBy('nombre')->get();

        $totalIngresosMes = collect($this->ingresos)
            ->sum(fn ($i) => $this->parsearImporte($i['importe'] ?? '') ?? 0);

        $mesDate = $this->mesSeleccionado . '-01';
        $gastosMesPorInmueble = PagoGastoNoArca::where('mes', $mesDate)
            ->whereHas('gasto', fn ($q) => $q->whereNotNull('id_inmueble'))
            ->with('gasto')
            ->get()
            ->groupBy(fn ($p) => $p->gasto->id_inmueble)
            ->map(fn ($pagos) => $pagos->sum('importe'));

        $catalogo = Inmueble::orderBy('nombre')->get();

        return view('livewire.finanzas.gestion-inmuebles', [
            'inmueblesActivos'     => $inmueblesActivos,
            'totalIngresosMes'     => $totalIngresosMes,
            'gastosMesPorInmueble' => $gastosMesPorInmueble,
            'catalogo'             => $catalogo,
            'mesLabel'             => $this->formatearMes($this->mesSeleccionado),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function parsearImporte(string $valor): ?float
    {
        $valor = trim($valor);
        if ($valor === '' || $valor === '-') return null;
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
