<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PeriodoFiscal extends Model
{
    use HasFactory, UsaUuid, LogsActivity;

    protected $table = 'periodos_fiscales';

    protected $fillable = [
        'periodo',
        'estado',
        'fecha_cierre',
        'fecha_presentacion',
        'numero_formulario',
        'observaciones',
    ];

    protected $casts = [
        'fecha_cierre'       => 'date',
        'fecha_presentacion' => 'date',
    ];

    const ESTADOS = [
        'abierto'    => 'Abierto',
        'cerrado'    => 'Cerrado',
        'presentado' => 'Presentado',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['periodo', 'estado', 'fecha_cierre', 'fecha_presentacion', 'numero_formulario'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function reintegros()
    {
        return $this->hasMany(ReintegroIva::class, 'id_periodo_fiscal');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Convierte '2026-06' → 'Junio 2026'
     */
    public function getPeriodoFormateadoAttribute(): string
    {
        return ucfirst(
            Carbon::createFromFormat('Y-m', $this->periodo)
                ->locale('es')
                ->isoFormat('MMMM YYYY')
        );
    }

    // ── Cálculos IVA ─────────────────────────────────────────────────────────

    /**
     * Suma el IVA de compras del período (crédito fiscal).
     */
    public function ivaCredito(): float
    {
        return (float) Compra::where('fecha', 'like', $this->periodo . '%')
            ->where('estado', '!=', 'cancelada')
            ->sum('iva_importe');
    }

    /**
     * Estima el IVA débito del período (10.5% sobre ventas confirmadas/cobradas).
     */
    public function ivaDebito(): float
    {
        $granos = (float) VentaGrano::where('fecha', 'like', $this->periodo . '%')
            ->whereNotIn('estado', ['cancelada', 'borrador'])
            ->sum('importe_total');

        $hacienda = (float) VentaHacienda::where('fecha', 'like', $this->periodo . '%')
            ->where('estado', '!=', 'cancelada')
            ->sum('importe_total');

        return ($granos + $hacienda) * 0.105;
    }

    /**
     * Saldo IVA: positivo = a pagar, negativo = a favor del contribuyente.
     */
    public function saldoIva(): float
    {
        return $this->ivaDebito() - $this->ivaCredito();
    }
}
