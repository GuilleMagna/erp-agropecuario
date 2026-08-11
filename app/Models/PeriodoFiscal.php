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
     *
     * @param  string|null  $idEmpresa  Si se pasa, filtra por esa empresa cruzando el
     *                                  scope multiempresa; si no, respeta la empresa activa.
     */
    public function ivaCredito(?string $idEmpresa = null): float
    {
        $query = Compra::where('fecha', 'like', $this->periodo . '%')
            ->where('estado', '!=', 'cancelada');

        if ($idEmpresa) {
            $query = $query->sinFiltroDeEmpresa()->where('id_empresa', $idEmpresa);
        }

        return (float) $query->sum('iva_importe');
    }

    /**
     * Estima el IVA débito del período (10.5% sobre ventas confirmadas/cobradas).
     *
     * @param  string|null  $idEmpresa  Si se pasa, filtra por esa empresa cruzando el
     *                                  scope multiempresa; si no, respeta la empresa activa.
     */
    public function ivaDebito(?string $idEmpresa = null): float
    {
        $granosQuery = VentaGrano::where('fecha', 'like', $this->periodo . '%')
            ->whereNotIn('estado', ['cancelada', 'borrador']);

        $haciendaQuery = VentaHacienda::where('fecha', 'like', $this->periodo . '%')
            ->where('estado', '!=', 'cancelada');

        if ($idEmpresa) {
            $granosQuery = $granosQuery->sinFiltroDeEmpresa()->where('id_empresa', $idEmpresa);
            $haciendaQuery = $haciendaQuery->sinFiltroDeEmpresa()->where('id_empresa', $idEmpresa);
        }

        $granos = (float) $granosQuery->sum('importe_total');
        $hacienda = (float) $haciendaQuery->sum('importe_total');

        return ($granos + $hacienda) * 0.105;
    }

    /**
     * Suma el IVA retenido en ventas de granos confirmadas/cobradas del período.
     */
    public function ivaRetenido(?string $idEmpresa = null): float
    {
        $query = VentaGrano::where('fecha', 'like', $this->periodo . '%')
            ->whereNotIn('estado', ['cancelada', 'borrador']);

        if ($idEmpresa) {
            $query = $query->sinFiltroDeEmpresa()->where('id_empresa', $idEmpresa);
        }

        return (float) $query->sum('ret_iva');
    }
    /**
     * Suma las devoluciones de IVA acreditadas en el período.
     */
    public function ivaDevolucion(?string $idEmpresa = null): float
    {
        $query = ReintegroIva::where('periodo', $this->periodo)
            ->where('estado', 'acreditado');

        if ($idEmpresa) {
            $query = $query->sinFiltroDeEmpresa()->where('id_empresa', $idEmpresa);
        }

        return (float) $query->sum('importe');
    }
    public function saldoTecnicoIva(?string $idEmpresa = null): float
    {
        return $this->ivaDebito($idEmpresa) - $this->ivaCredito($idEmpresa);
    }
    /**
     * Saldo IVA luego de retenciones: positivo = a pagar, negativo = a favor.
     * Las devoluciones acreditadas se suman como importe utilizable del período.
     */
    public function saldoIva(?string $idEmpresa = null): float
    {
        $saldoTecnico = $this->saldoTecnicoIva($idEmpresa);
        $retenido = $this->ivaRetenido($idEmpresa);
        $devolucion = $this->ivaDevolucion($idEmpresa);

        return $saldoTecnico >= 0
            ? $saldoTecnico - $retenido + $devolucion
            : $saldoTecnico + $retenido - $devolucion;
    }
}
