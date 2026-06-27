<?php

namespace App\Livewire\Feedlot;

use App\Models\Corral;
use App\Models\Establecimiento;
use App\Models\Tropa;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionTropas extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // Filtros
    public string $filtroCorral          = '';
    public string $filtroEstado          = 'activa';
    public string $filtroEstablecimiento = '';

    // Modal
    public bool    $modalAbierto    = false;
    public bool    $modoEdicion     = false;
    public ?string $tropaEditandoId = null;

    // Campos del formulario
    public string $nombre                    = '';
    public string $idCorral                  = '';
    public string $idEstablecimiento         = '';
    public string $categoria                 = '';
    public string $cantidadCabezas           = '';
    public string $fechaEntrada              = '';
    public string $fechaSalidaEstimada       = '';
    public string $fechaSalidaReal           = '';
    public string $pesoPesoPromedioEntradaKg = '';
    public string $pesoPesoPromedioSalidaKg  = '';
    public string $objetivoGananciaDiariaKg  = '';
    public string $estado                    = 'activa';
    public string $observaciones             = '';

    protected function rules(): array
    {
        $finalizando = $this->estado === 'finalizada';

        return [
            'nombre'                    => 'required|string|max:100',
            'idCorral'                  => 'nullable|exists:corrales,id',
            'idEstablecimiento'         => 'nullable|exists:establecimientos,id',
            'categoria'                 => 'required|string|in:' . implode(',', array_keys(Tropa::CATEGORIAS)),
            'cantidadCabezas'           => 'required|integer|min:1',
            'fechaEntrada'              => 'required|date',
            'fechaSalidaEstimada'       => 'nullable|date|after_or_equal:fechaEntrada',
            'fechaSalidaReal'           => $finalizando ? 'required|date' : 'nullable|date',
            'pesoPesoPromedioEntradaKg' => 'nullable|numeric|min:0',
            'pesoPesoPromedioSalidaKg'  => $finalizando ? 'required|numeric|min:1' : 'nullable|numeric|min:0',
            'objetivoGananciaDiariaKg'  => 'nullable|numeric|min:0',
            'estado'                    => 'required|string|in:' . implode(',', array_keys(Tropa::ESTADOS)),
            'observaciones'             => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'nombre.required'            => 'El nombre de la tropa es obligatorio.',
        'categoria.required'         => 'La categoría es obligatoria.',
        'cantidadCabezas.required'   => 'La cantidad de cabezas es obligatoria.',
        'cantidadCabezas.min'        => 'Debe haber al menos 1 cabeza.',
        'fechaEntrada.required'      => 'La fecha de entrada es obligatoria.',
        'fechaSalidaReal.required'   => 'Para finalizar la tropa se requiere la fecha de salida real.',
        'pesoPesoPromedioSalidaKg.required' => 'Para finalizar la tropa se requiere el peso de salida.',
    ];

    public function mount(): void
    {
        $this->fechaEntrada = now()->format('Y-m-d');
    }

    public function updatingFiltroCorral(): void          { $this->resetPage(); }
    public function updatingFiltroEstado(): void          { $this->resetPage(); }
    public function updatingFiltroEstablecimiento(): void { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('feedlot.tropas.gestionar');
        $this->limpiarFormulario();
        $this->fechaEntrada = now()->format('Y-m-d');
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('feedlot.tropas.gestionar');

        $tropa = Tropa::findOrFail($id);

        $this->tropaEditandoId             = $id;
        $this->nombre                      = $tropa->nombre;
        $this->idCorral                    = $tropa->id_corral ?? '';
        $this->idEstablecimiento           = $tropa->id_establecimiento ?? '';
        $this->categoria                   = $tropa->categoria;
        $this->cantidadCabezas             = (string)$tropa->cantidad_cabezas;
        $this->fechaEntrada                = $tropa->fecha_entrada->format('Y-m-d');
        $this->fechaSalidaEstimada         = $tropa->fecha_salida_estimada?->format('Y-m-d') ?? '';
        $this->fechaSalidaReal             = $tropa->fecha_salida_real?->format('Y-m-d') ?? '';
        $this->pesoPesoPromedioEntradaKg   = $tropa->peso_promedio_entrada_kg !== null ? (string)$tropa->peso_promedio_entrada_kg : '';
        $this->pesoPesoPromedioSalidaKg    = $tropa->peso_promedio_salida_kg !== null ? (string)$tropa->peso_promedio_salida_kg : '';
        $this->objetivoGananciaDiariaKg    = $tropa->objetivo_ganancia_diaria_kg !== null ? (string)$tropa->objetivo_ganancia_diaria_kg : '';
        $this->estado                      = $tropa->estado;
        $this->observaciones               = $tropa->observaciones ?? '';

        $this->modoEdicion  = true;
        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        $this->validate();

        $datos = [
            'nombre'                      => $this->nombre,
            'id_corral'                   => $this->idCorral ?: null,
            'id_establecimiento'          => $this->idEstablecimiento ?: null,
            'categoria'                   => $this->categoria,
            'cantidad_cabezas'            => (int)$this->cantidadCabezas,
            'fecha_entrada'               => $this->fechaEntrada,
            'fecha_salida_estimada'       => $this->fechaSalidaEstimada ?: null,
            'fecha_salida_real'           => $this->fechaSalidaReal ?: null,
            'peso_promedio_entrada_kg'    => $this->pesoPesoPromedioEntradaKg !== '' ? (float)$this->pesoPesoPromedioEntradaKg : null,
            'peso_promedio_salida_kg'     => $this->pesoPesoPromedioSalidaKg !== '' ? (float)$this->pesoPesoPromedioSalidaKg : null,
            'objetivo_ganancia_diaria_kg' => $this->objetivoGananciaDiariaKg !== '' ? (float)$this->objetivoGananciaDiariaKg : null,
            'estado'                      => $this->estado,
            'observaciones'               => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            Gate::authorize('feedlot.tropas.gestionar');
            $tropa = Tropa::findOrFail($this->tropaEditandoId);
            $tropa->update($datos);
            session()->flash('success', "Tropa '{$tropa->nombre}' actualizada correctamente.");
        } else {
            Gate::authorize('feedlot.tropas.gestionar');
            Tropa::create(array_merge($datos, ['id_empresa' => auth()->user()->id_empresa]));
            session()->flash('success', "Tropa '{$this->nombre}' creada correctamente.");
        }

        $this->cerrarModal();
    }

    public function cancelarTropa(string $id): void
    {
        Gate::authorize('feedlot.tropas.gestionar');
        $tropa = Tropa::findOrFail($id);
        $tropa->update(['estado' => 'cancelada', 'fecha_salida_real' => now()->format('Y-m-d')]);
        session()->flash('success', "Tropa '{$tropa->nombre}' cancelada.");
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'tropaEditandoId', 'nombre', 'idCorral', 'idEstablecimiento',
            'categoria', 'cantidadCabezas', 'fechaEntrada',
            'fechaSalidaEstimada', 'fechaSalidaReal',
            'pesoPesoPromedioEntradaKg', 'pesoPesoPromedioSalidaKg',
            'objetivoGananciaDiariaKg', 'observaciones',
        ]);
        $this->estado      = 'activa';
        $this->fechaEntrada = now()->format('Y-m-d');
        $this->resetValidation();
    }

    public function render()
    {
        $tropas = Tropa::query()
            ->with(['corral', 'establecimiento'])
            ->when($this->filtroCorral, fn($q) => $q->where('id_corral', $this->filtroCorral))
            ->when($this->filtroEstado, fn($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroEstablecimiento, fn($q) => $q->where('id_establecimiento', $this->filtroEstablecimiento))
            ->orderByRaw("CASE estado WHEN 'activa' THEN 0 ELSE 1 END")
            ->orderBy('fecha_entrada', 'desc')
            ->paginate(15);

        // KPIs globales (sin filtro de estado para mostrar totales reales)
        $totalActivas        = Tropa::activas()->count();
        $totalCabezasFeedlot = Tropa::activas()->sum('cantidad_cabezas');
        $promediosDias       = Tropa::activas()->get()->avg(fn($t) => $t->dias_en_feedlot);

        $corrales         = Corral::activos()->orderBy('nombre')->get();
        $establecimientos = Establecimiento::orderBy('nombre')->get();

        return view('livewire.feedlot.gestion-tropas', compact(
            'tropas', 'corrales', 'establecimientos',
            'totalActivas', 'totalCabezasFeedlot', 'promediosDias',
        ));
    }
}
