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
    use HasFactory, LogsActivity, PerteneceAEmpresa, UsaUuid;

    protected $table = 'proveedores';

    protected $fillable = [
        'id_empresa',
        'nombre', 'razon_social', 'cuit', 'rubro', 'actividad', 'zona',
        'telefono', 'email', 'direccion', 'ciudad', 'provincia',
        'observaciones', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Clasificación de gasto por proveedor, tomada de la hoja RUBROS del Excel
     * "Facturas AÑO 2024.xlsx" (149 CUITs clasificados a mano por el usuario).
     */
    const RUBROS = [
        'otro' => 'Otro',
        'insumos' => 'Insumos',
        'varios' => 'Varios',
        'comercializacion' => 'Comercialización',
        'mantenimiento' => 'Mantenimiento',
        'reparaciones' => 'Reparaciones',
        'labores_servicios' => 'Labores / Servicios',
        'sanidad' => 'Sanidad',
        'transporte' => 'Transporte / Flete',
        'empleados' => 'Empleados',
        'alimento' => 'Alimento',
        'administracion' => 'Administración',
        'esporadicos' => 'Esporádicos',
        'asesoramiento' => 'Asesoramiento',
        'alquileres' => 'Alquileres',
        'bien_capital' => 'Bien de capital',
        'combustible' => 'Combustible',
    ];

    const ACTIVIDADES = [
        'general' => 'General',
        'agricultura' => 'Agricultura',
        'ganaderia' => 'Ganadería',
        'inversiones' => 'Inversiones',
    ];

    const ZONAS = [
        'general' => 'General',
        'el_trebol' => 'El Trébol',
        'corrientes' => 'Corrientes',
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

    public function getActividadLabelAttribute(): string
    {
        return self::ACTIVIDADES[$this->actividad] ?? ($this->actividad ?? '—');
    }

    public function getZonaLabelAttribute(): string
    {
        return self::ZONAS[$this->zona] ?? ($this->zona ?? '—');
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
