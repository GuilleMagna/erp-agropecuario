<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReintegroIva extends Model
{
    use HasFactory, UsaUuid, LogsActivity;

    protected $table = 'reintegros_iva';

    protected $fillable = [
        'periodo',
        'id_periodo_fiscal',
        'importe',
        'fecha_presentacion',
        'fecha_acreditacion',
        'estado',
        'numero_expediente',
        'observaciones',
    ];

    protected $casts = [
        'importe'            => 'decimal:2',
        'fecha_presentacion' => 'date',
        'fecha_acreditacion' => 'date',
    ];

    const ESTADOS = [
        'pendiente'   => 'Pendiente',
        'acreditado'  => 'Acreditado',
        'rechazado'   => 'Rechazado',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['periodo', 'importe', 'estado', 'numero_expediente', 'fecha_acreditacion'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function periodoFiscal()
    {
        return $this->belongsTo(PeriodoFiscal::class, 'id_periodo_fiscal');
    }
}
