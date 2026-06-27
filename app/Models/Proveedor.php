<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Proveedor extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre', 'razon_social', 'cuit', 'rubro',
        'telefono', 'email', 'direccion', 'ciudad', 'provincia',
        'observaciones', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    const RUBROS = [
        'insumos_agricolas' => 'Insumos agrícolas',
        'veterinaria'       => 'Veterinaria',
        'maquinaria'        => 'Maquinaria y equipos',
        'combustible'       => 'Combustible',
        'semillas'          => 'Semillas',
        'servicios'         => 'Servicios',
        'transporte'        => 'Transporte / Flete',
        'otro'              => 'Otro',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'razon_social', 'cuit', 'rubro', 'telefono', 'email', 'activo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRubroLabelAttribute(): string
    {
        return self::RUBROS[$this->rubro] ?? ($this->rubro ?? '—');
    }

    public function compras()
    {
        return $this->hasMany(Compra::class, 'id_proveedor');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
