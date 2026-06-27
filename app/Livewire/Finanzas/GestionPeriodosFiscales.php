<?php

namespace App\Livewire\Finanzas;

use App\Models\PeriodoFiscal;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionPeriodosFiscales extends Component
{
    use WithPagination;

    public string  $filtroEstado    = '';
    public ?string $mostrarDetalle  = null;

    public bool    $modalAbierto      = false;
    public bool    $modoEdicion       = false;
    public ?string $periodoEditandoId = null;

    // Campos del formulario
    public string $periodo            = '';
    public string $estado             = 'abierto';
    public string $fecha_cierre       = '';
    public string $fecha_presentacion = '';
    public string $numero_formulario  = '';
    public string $observaciones      = '';

    protected function rules(): array
    {
        return [
            'periodo'            => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'estado'             => 'required|in:' . implode(',', array_keys(PeriodoFiscal::ESTADOS)),
            'fecha_cierre'       => 'nullable|date',
            'fecha_presentacion' => 'nullable|date',
            'numero_formulario'  => 'nullable|string|max:50',
            'observaciones'      => 'nullable|string',
        ];
    }

    protected $messages = [
        'periodo.required' => 'El período es obligatorio.',
        'periodo.regex'    => 'El período debe tener el formato YYYY-MM (ej: 2026-06).',
    ];

    public function updatedFiltroEstado(): void { $this->resetPage(); }

    // ── Modal ─────────────────────────────────────────────────────────────────

    public function abrirModalCrear(): void
    {
        Gate::authorize('finanzas.periodos.gestionar');
        $this->resetForm();
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('finanzas.periodos.gestionar');
        $pf = PeriodoFiscal::findOrFail($id);
        $this->periodoEditandoId  = $id;
        $this->periodo            = $pf->periodo;
        $this->estado             = $pf->estado;
        $this->fecha_cierre       = $pf->fecha_cierre?->format('Y-m-d') ?? '';
        $this->fecha_presentacion = $pf->fecha_presentacion?->format('Y-m-d') ?? '';
        $this->numero_formulario  = $pf->numero_formulario ?? '';
        $this->observaciones      = $pf->observaciones ?? '';
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
        Gate::authorize('finanzas.periodos.gestionar');
        $this->validate();

        $data = [
            'periodo'            => $this->periodo,
            'estado'             => $this->estado,
            'fecha_cierre'       => $this->fecha_cierre ?: null,
            'fecha_presentacion' => $this->fecha_presentacion ?: null,
            'numero_formulario'  => $this->numero_formulario ?: null,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            PeriodoFiscal::findOrFail($this->periodoEditandoId)->update($data);
            session()->flash('success', 'Período fiscal actualizado correctamente.');
        } else {
            PeriodoFiscal::create($data);
            session()->flash('success', 'Período fiscal creado correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    // ── Acciones de estado ────────────────────────────────────────────────────

    public function eliminar(string $id): void
    {
        Gate::authorize('finanzas.periodos.gestionar');
        $pf = PeriodoFiscal::findOrFail($id);

        if ($pf->estado !== 'abierto') {
            session()->flash('error', 'Solo se pueden eliminar períodos en estado "Abierto".');
            return;
        }

        $pf->delete();
        session()->flash('success', 'Período fiscal eliminado.');
    }

    public function cerrarPeriodo(string $id): void
    {
        Gate::authorize('finanzas.periodos.gestionar');
        PeriodoFiscal::findOrFail($id)->update([
            'estado'       => 'cerrado',
            'fecha_cierre' => now()->toDateString(),
        ]);
        session()->flash('success', 'Período cerrado correctamente.');
    }

    public function reabrirPeriodo(string $id): void
    {
        Gate::authorize('finanzas.periodos.gestionar');
        PeriodoFiscal::findOrFail($id)->update([
            'estado'       => 'abierto',
            'fecha_cierre' => null,
        ]);
        session()->flash('success', 'Período reabierto correctamente.');
    }

    public function marcarPresentado(string $id): void
    {
        Gate::authorize('finanzas.periodos.gestionar');
        $pf = PeriodoFiscal::findOrFail($id);
        $updates = ['estado' => 'presentado'];
        if (!$pf->fecha_presentacion) {
            $updates['fecha_presentacion'] = now()->toDateString();
        }
        $pf->update($updates);
        session()->flash('success', 'Período marcado como presentado.');
    }

    public function toggleDetalle(string $id): void
    {
        $this->mostrarDetalle = ($this->mostrarDetalle === $id) ? null : $id;
    }

    // ── Reset ─────────────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->periodoEditandoId  = null;
        $this->periodo            = '';
        $this->estado             = 'abierto';
        $this->fecha_cierre       = '';
        $this->fecha_presentacion = '';
        $this->numero_formulario  = '';
        $this->observaciones      = '';
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $periodos = PeriodoFiscal::query()
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->withCount('reintegros')
            ->orderByDesc('periodo')
            ->paginate(20);

        // Calcular IVA para cada período listado
        $ivaData = [];
        foreach ($periodos as $pf) {
            $credito = $pf->ivaCredito();
            $debito  = $pf->ivaDebito();
            $saldo   = $pf->saldoIva();
            $ivaData[$pf->id] = compact('credito', 'debito', 'saldo');
        }

        return view('livewire.finanzas.gestion-periodos-fiscales', [
            'periodos' => $periodos,
            'estados'  => PeriodoFiscal::ESTADOS,
            'ivaData'  => $ivaData,
        ]);
    }
}
