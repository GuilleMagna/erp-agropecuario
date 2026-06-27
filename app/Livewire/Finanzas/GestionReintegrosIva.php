<?php

namespace App\Livewire\Finanzas;

use App\Models\PeriodoFiscal;
use App\Models\ReintegroIva;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionReintegrosIva extends Component
{
    use WithPagination;

    public string $filtroEstado  = '';
    public string $filtroPeriodo = '';

    public bool    $modalAbierto       = false;
    public bool    $modoEdicion        = false;
    public ?string $reintegroEditandoId = null;

    // Campos del formulario
    public string  $periodo             = '';
    public string  $id_periodo_fiscal   = '';
    public string  $importe             = '';
    public string  $fecha_presentacion  = '';
    public string  $fecha_acreditacion  = '';
    public string  $estado              = 'pendiente';
    public string  $numero_expediente   = '';
    public string  $observaciones       = '';

    protected function rules(): array
    {
        return [
            'periodo'            => 'required|regex:/^\d{4}-\d{2}$/',
            'id_periodo_fiscal'  => 'nullable|exists:periodos_fiscales,id',
            'importe'            => 'required|numeric|min:0.01',
            'fecha_presentacion' => 'nullable|date',
            'fecha_acreditacion' => 'nullable|date',
            'estado'             => 'required|in:' . implode(',', array_keys(ReintegroIva::ESTADOS)),
            'numero_expediente'  => 'nullable|string|max:100',
            'observaciones'      => 'nullable|string',
        ];
    }

    protected $messages = [
        'periodo.required' => 'El período es obligatorio.',
        'periodo.regex'    => 'El período debe tener el formato YYYY-MM.',
        'importe.required' => 'El importe es obligatorio.',
        'importe.min'      => 'El importe debe ser mayor a cero.',
    ];

    public function updatedFiltroEstado(): void  { $this->resetPage(); }
    public function updatedFiltroPeriodo(): void { $this->resetPage(); }

    // ── Modal ─────────────────────────────────────────────────────────────────

    public function abrirModalCrear(): void
    {
        Gate::authorize('finanzas.reintegros.gestionar');
        $this->resetForm();
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('finanzas.reintegros.gestionar');
        $r = ReintegroIva::findOrFail($id);
        $this->reintegroEditandoId = $id;
        $this->periodo             = $r->periodo;
        $this->id_periodo_fiscal   = $r->id_periodo_fiscal ?? '';
        $this->importe             = (string) $r->importe;
        $this->fecha_presentacion  = $r->fecha_presentacion?->format('Y-m-d') ?? '';
        $this->fecha_acreditacion  = $r->fecha_acreditacion?->format('Y-m-d') ?? '';
        $this->estado              = $r->estado;
        $this->numero_expediente   = $r->numero_expediente ?? '';
        $this->observaciones       = $r->observaciones ?? '';
        $this->modoEdicion         = true;
        $this->modalAbierto        = true;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function guardar(): void
    {
        Gate::authorize('finanzas.reintegros.gestionar');
        $this->validate();

        // Si no se seleccionó período fiscal pero hay período, intentar asociarlo automáticamente
        $idPeriodoFiscal = $this->id_periodo_fiscal ?: null;
        if (!$idPeriodoFiscal && $this->periodo) {
            $pf = PeriodoFiscal::where('periodo', $this->periodo)->first();
            if ($pf) {
                $idPeriodoFiscal = $pf->id;
            }
        }

        $data = [
            'periodo'            => $this->periodo,
            'id_periodo_fiscal'  => $idPeriodoFiscal,
            'importe'            => (float) $this->importe,
            'fecha_presentacion' => $this->fecha_presentacion ?: null,
            'fecha_acreditacion' => $this->fecha_acreditacion ?: null,
            'estado'             => $this->estado,
            'numero_expediente'  => $this->numero_expediente ?: null,
            'observaciones'      => $this->observaciones ?: null,
        ];

        if ($this->modoEdicion) {
            ReintegroIva::findOrFail($this->reintegroEditandoId)->update($data);
            session()->flash('success', 'Reintegro IVA actualizado correctamente.');
        } else {
            ReintegroIva::create($data);
            session()->flash('success', 'Reintegro IVA registrado correctamente.');
        }

        $this->modalAbierto = false;
        $this->resetForm();
    }

    public function eliminar(string $id): void
    {
        Gate::authorize('finanzas.reintegros.gestionar');
        ReintegroIva::findOrFail($id)->delete();
        session()->flash('success', 'Reintegro eliminado correctamente.');
    }

    // ── Reset ─────────────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->reintegroEditandoId = null;
        $this->periodo             = '';
        $this->id_periodo_fiscal   = '';
        $this->importe             = '';
        $this->fecha_presentacion  = '';
        $this->fecha_acreditacion  = '';
        $this->estado              = 'pendiente';
        $this->numero_expediente   = '';
        $this->observaciones       = '';
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $reintegros = ReintegroIva::query()
            ->when($this->filtroEstado,  fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroPeriodo, fn ($q) => $q->where('periodo', $this->filtroPeriodo))
            ->with('periodoFiscal')
            ->orderByDesc('periodo')
            ->orderByDesc('created_at')
            ->paginate(20);

        $totalPendiente  = ReintegroIva::where('estado', 'pendiente')->sum('importe');
        $totalAcreditado = ReintegroIva::where('estado', 'acreditado')->sum('importe');

        $periodosFiscales = PeriodoFiscal::orderByDesc('periodo')->get();

        return view('livewire.finanzas.gestion-reintegros-iva', [
            'reintegros'       => $reintegros,
            'estados'          => ReintegroIva::ESTADOS,
            'totalPendiente'   => (float) $totalPendiente,
            'totalAcreditado'  => (float) $totalAcreditado,
            'periodosFiscales' => $periodosFiscales,
        ]);
    }
}
