<?php

namespace App\Livewire;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\Cuenta;
use App\Models\Insumo;
use App\Models\Jornal;
use App\Models\Siembra;
use App\Models\Transaccion;
use App\Models\VentaGrano;
use App\Models\VentaHacienda;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        // === KPIs (cada uno sólo se computa si el usuario tiene permiso) ===
        $totalAnimales = $user->can('ganaderia.animales.ver')
            ? Animal::activos()->count()
            : null;

        $superficieSembrada = $user->can('agricultura.siembra.ver')
            ? (float) Siembra::whereIn('estado', ['sembrada', 'en_cultivo'])->sum('superficie_sembrada_ha')
            : null;

        $saldoCuentas = $user->can('finanzas.cuentas.ver')
            ? $this->calcularSaldoCuentas()
            : null;

        $comprasPendientesCant = $user->can('compras.ver')
            ? Compra::where('estado', 'pendiente')->count()
            : null;

        $ventasMes = ($user->can('ventas.granos.ver') || $user->can('ventas.hacienda.ver'))
            ? $this->calcularVentasMes($user)
            : null;

        $jornalesPendientes = $user->can('rrhh.jornales.ver')
            ? (float) Jornal::where('estado', 'pendiente')->sum('importe')
            : null;

        // === Alertas ===
        $insumosAlerta = $user->can('insumos.catalogo.ver')
            ? $this->getInsumosAlerta()
            : collect();

        $comprasVencidas = $user->can('compras.ver')
            ? Compra::whereIn('estado', ['pendiente'])
                ->whereNotNull('fecha_vencimiento')
                ->where('fecha_vencimiento', '<', now()->toDateString())
                ->count()
            : 0;

        // === Actividad reciente ===
        $ultimasVentasGranos = $user->can('ventas.granos.ver')
            ? VentaGrano::latest('fecha')->limit(6)->get()
            : collect();

        $ultimasVentasHacienda = $user->can('ventas.hacienda.ver')
            ? VentaHacienda::latest('fecha')->limit(6)->get()
            : collect();

        $ultimasCompras = $user->can('compras.ver')
            ? Compra::with('proveedor')->latest('fecha')->limit(6)->get()
            : collect();

        // === Hacienda por categoría ===
        $stockHacienda = $user->can('ganaderia.animales.ver')
            ? Animal::activos()
                ->selectRaw('categoria, count(*) as total, avg(peso_actual_kg) as peso_promedio')
                ->groupBy('categoria')
                ->orderBy('total', 'desc')
                ->get()
            : collect();

        // === Siembras activas ===
        $siembrasActivas = $user->can('agricultura.siembra.ver')
            ? Siembra::whereIn('estado', ['sembrada', 'en_cultivo'])
                ->with(['lote', 'campana'])
                ->orderBy('fecha_siembra', 'desc')
                ->limit(6)
                ->get()
            : collect();

        // === Flujo financiero del mes actual ===
        $flujoMes = $user->can('finanzas.transacciones.ver')
            ? $this->calcularFlujoMes()
            : null;

        return view('livewire.dashboard', compact(
            'totalAnimales', 'superficieSembrada', 'saldoCuentas',
            'comprasPendientesCant', 'ventasMes', 'jornalesPendientes',
            'insumosAlerta', 'comprasVencidas',
            'ultimasVentasGranos', 'ultimasVentasHacienda', 'ultimasCompras',
            'stockHacienda', 'siembrasActivas', 'flujoMes',
        ));
    }

    private function calcularSaldoCuentas(): float
    {
        return Cuenta::activas()
            ->withSum(['transacciones as total_ingresos' => fn($q) => $q->where('tipo', 'ingreso')], 'importe')
            ->withSum(['transacciones as total_egresos'  => fn($q) => $q->where('tipo', 'egreso')], 'importe')
            ->get()
            ->sum(fn($c) => (float) $c->saldo_inicial
                + (float) ($c->total_ingresos ?? 0)
                - (float) ($c->total_egresos ?? 0)
            );
    }

    private function calcularVentasMes($user): array
    {
        $desde = now()->startOfMonth()->format('Y-m-d');
        $hasta = now()->format('Y-m-d');
        $granos   = 0.0;
        $hacienda = 0.0;

        if ($user->can('ventas.granos.ver')) {
            $granos = (float) VentaGrano::whereBetween('fecha', [$desde, $hasta])
                ->whereNotIn('estado', ['cancelada', 'borrador'])
                ->sum('importe_total');
        }
        if ($user->can('ventas.hacienda.ver')) {
            $hacienda = (float) VentaHacienda::whereBetween('fecha', [$desde, $hasta])
                ->whereNotIn('estado', ['cancelada'])
                ->sum('importe_total');
        }

        return [
            'granos'   => $granos,
            'hacienda' => $hacienda,
            'total'    => $granos + $hacienda,
        ];
    }

    private function getInsumosAlerta()
    {
        return Insumo::activos()
            ->whereNotNull('stock_minimo')
            ->withSum(['movimientosInsumos as total_entradas' => fn($q) =>
                $q->whereIn('tipo', ['entrada', 'ajuste_positivo'])
            ], 'cantidad')
            ->withSum(['movimientosInsumos as total_salidas' => fn($q) =>
                $q->whereNotIn('tipo', ['entrada', 'ajuste_positivo'])
            ], 'cantidad')
            ->get()
            ->map(function ($ins) {
                $ins->stock_actual = (float) ($ins->total_entradas ?? 0) - (float) ($ins->total_salidas ?? 0);
                return $ins;
            })
            ->filter(fn($ins) => $ins->stock_actual < (float) $ins->stock_minimo)
            ->values();
    }

    private function calcularFlujoMes(): array
    {
        $desde = now()->startOfMonth()->format('Y-m-d');
        $hasta = now()->format('Y-m-d');

        $ingresos = (float) Transaccion::whereBetween('fecha', [$desde, $hasta])->where('tipo', 'ingreso')->sum('importe');
        $egresos  = (float) Transaccion::whereBetween('fecha', [$desde, $hasta])->where('tipo', 'egreso')->sum('importe');

        return [
            'ingresos' => $ingresos,
            'egresos'  => $egresos,
            'neto'     => $ingresos - $egresos,
        ];
    }
}
