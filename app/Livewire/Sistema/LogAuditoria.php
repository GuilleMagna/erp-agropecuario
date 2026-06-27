<?php

namespace App\Livewire\Sistema;

use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class LogAuditoria extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $filtroUsuario = '';
    public string $filtroModelo  = '';
    public string $filtroAccion  = '';
    public string $filtroDesde   = '';
    public string $filtroHasta   = '';

    // Labels amigables para tipos de sujeto
    const MODELOS_LABEL = [
        'App\Models\Usuario'           => 'Usuario',
        'App\Models\Animal'            => 'Animal',
        'App\Models\Movimiento'        => 'Movimiento ganadería',
        'App\Models\Siembra'           => 'Siembra',
        'App\Models\Labor'             => 'Labor agrícola',
        'App\Models\Cosecha'           => 'Cosecha',
        'App\Models\Insumo'            => 'Insumo',
        'App\Models\MovimientoInsumo'  => 'Movimiento insumo',
        'App\Models\Compra'            => 'Compra',
        'App\Models\Proveedor'         => 'Proveedor',
        'App\Models\VentaGrano'        => 'Venta de granos',
        'App\Models\VentaHacienda'     => 'Venta de hacienda',
        'App\Models\Transaccion'       => 'Transacción',
        'App\Models\Cuenta'            => 'Cuenta',
        'App\Models\Empleado'          => 'Empleado',
        'App\Models\Jornal'            => 'Jornal',
        'App\Models\Establecimiento'   => 'Establecimiento',
        'App\Models\Lote'              => 'Lote',
        'App\Models\Campana'           => 'Campaña',
    ];

    public function mount(): void
    {
        Gate::authorize('auditoria.ver');
        $this->filtroDesde = now()->subDays(30)->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
    }

    public function updatingFiltroUsuario(): void { $this->resetPage(); }
    public function updatingFiltroModelo(): void  { $this->resetPage(); }
    public function updatingFiltroAccion(): void  { $this->resetPage(); }
    public function updatingFiltroDesde(): void   { $this->resetPage(); }
    public function updatingFiltroHasta(): void   { $this->resetPage(); }

    public function render()
    {
        $query = Activity::query()
            ->with('causer')
            ->when($this->filtroDesde, fn($q) => $q->whereDate('created_at', '>=', $this->filtroDesde))
            ->when($this->filtroHasta, fn($q) => $q->whereDate('created_at', '<=', $this->filtroHasta))
            ->when($this->filtroModelo, fn($q) => $q->where('subject_type', $this->filtroModelo))
            ->when($this->filtroAccion, fn($q) => $q->where('event', $this->filtroAccion))
            ->when($this->filtroUsuario, function ($q) {
                $ids = Usuario::where('nombre', 'like', "%{$this->filtroUsuario}%")
                    ->orWhere('apellido', 'like', "%{$this->filtroUsuario}%")
                    ->pluck('id');
                $q->where('causer_type', Usuario::class)->whereIn('causer_id', $ids);
            })
            ->latest();

        $actividades = $query->paginate(25);

        // Tipos de sujetos presentes en el log (para el filtro)
        $tiposModelo = Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->sort()
            ->values();

        // Tipos de eventos distintos
        $tiposAccion = Activity::query()
            ->whereNotNull('event')
            ->distinct()
            ->pluck('event')
            ->sort()
            ->values();

        return view('livewire.sistema.log-auditoria', [
            'actividades'  => $actividades,
            'tiposModelo'  => $tiposModelo,
            'tiposAccion'  => $tiposAccion,
            'modelosLabel' => self::MODELOS_LABEL,
        ]);
    }
}
