<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Jornal extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'jornales';

    protected $fillable = [
        'id_empleado', 'id_establecimiento',
        'fecha', 'tipo_jornada', 'horas_trabajadas',
        'tarea', 'importe', 'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'horas_trabajadas' => 'decimal:2',
        'importe'          => 'decimal:2',
    ];

    const TIPOS_JORNADA = [
        'completa'    => 'Jornada completa',
        'media'       => 'Media jornada',
        'hora_extra'  => 'Horas extra',
        'feriado'     => 'Feriado / Domingo',
        'ausencia'    => 'Ausencia',
    ];

    const ESTADOS = [
        'pendiente' => 'Pendiente',
        'liquidado' => 'Liquidado',
        'anulado'   => 'Anulado',
    ];

    const TIPOS_POSITIVOS = ['completa', 'media', 'hora_extra', 'feriado'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id_empleado', 'id_establecimiento', 'fecha', 'tipo_jornada', 'horas_trabajadas', 'importe', 'estado'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getTipoJornadaLabelAttribute(): string
    {
        return self::TIPOS_JORNADA[$this->tipo_jornada] ?? $this->tipo_jornada;
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }
}
