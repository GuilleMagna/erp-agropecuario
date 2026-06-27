<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Empleado extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'empleados';

    protected $fillable = [
        'id_establecimiento',
        'nombre', 'apellido', 'dni', 'cuil',
        'tipo_contrato', 'categoria',
        'fecha_ingreso', 'fecha_egreso',
        'sueldo_base', 'telefono', 'email',
        'direccion', 'cbu', 'banco',
        'activo', 'observaciones',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_egreso'  => 'date',
        'sueldo_base'   => 'decimal:2',
        'activo'        => 'boolean',
    ];

    const TIPOS_CONTRATO = [
        'relacion_dependencia' => 'Relación de dependencia',
        'jornal'               => 'Jornalero',
        'monotributo'          => 'Monotributista',
        'plazo_fijo'           => 'Contrato a plazo fijo',
        'pasante'              => 'Pasante',
        'eventual'             => 'Eventual / Zafra',
    ];

    const CATEGORIAS = [
        'peon_general'   => 'Peón general',
        'tractorista'    => 'Tractorista',
        'maquinista'     => 'Maquinista / Operario',
        'capataz'        => 'Capataz',
        'ordeñador'      => 'Ordeñador',
        'veterinario'    => 'Veterinario',
        'agronomo'       => 'Agrónomo',
        'administrativo' => 'Administrativo',
        'otro'           => 'Otro',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'apellido', 'dni', 'cuil', 'tipo_contrato', 'categoria', 'sueldo_base', 'activo', 'id_establecimiento'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellido}, {$this->nombre}";
    }

    public function getTipoContratoLabelAttribute(): string
    {
        return self::TIPOS_CONTRATO[$this->tipo_contrato] ?? $this->tipo_contrato;
    }

    public function getCategoriaLabelAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? ($this->categoria ?? '—');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function jornales()
    {
        return $this->hasMany(Jornal::class, 'id_empleado');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
