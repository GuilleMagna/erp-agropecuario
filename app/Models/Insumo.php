<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Insumo extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'insumos';

    protected $fillable = [
        'nombre', 'codigo', 'tipo', 'unidad', 'marca',
        'descripcion', 'stock_minimo', 'precio_referencia', 'activo',
    ];

    protected $casts = [
        'activo'            => 'boolean',
        'stock_minimo'      => 'decimal:2',
        'precio_referencia' => 'decimal:2',
    ];

    const TIPOS = [
        'semilla'      => 'Semilla',
        'agroquimico'  => 'Agroquímico',
        'fertilizante' => 'Fertilizante',
        'combustible'  => 'Combustible',
        'veterinario'  => 'Veterinario',
        'herramienta'  => 'Herramienta / Equipo',
        'repuesto'     => 'Repuesto',
        'otro'         => 'Otro',
    ];

    const UNIDADES = [
        'kg'     => 'Kilogramos (kg)',
        'lt'     => 'Litros (lt)',
        'tn'     => 'Toneladas (tn)',
        'unidad' => 'Unidad',
        'saco'   => 'Saco',
        'bolsa'  => 'Bolsa',
        'frasco' => 'Frasco',
        'bidon'  => 'Bidón',
        'cm3'    => 'cc / ml',
        'dosis'  => 'Dosis',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'codigo', 'tipo', 'unidad', 'marca', 'activo', 'stock_minimo', 'precio_referencia'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getUnidadLabelAttribute(): string
    {
        return self::UNIDADES[$this->unidad] ?? $this->unidad;
    }

    public function movimientosInsumos()
    {
        return $this->hasMany(MovimientoInsumo::class, 'id_insumo');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
