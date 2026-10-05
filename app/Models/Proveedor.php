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
        'nombre', 'razon_social', 'cuit', 'rubro', 'actividad',
        'telefono', 'email', 'direccion', 'ciudad', 'provincia',
        'observaciones', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Catálogo base: categorías históricas de la planilla y rubros agropecuarios.
     * Rubro agrega las categorías y configuraciones propias de cada empresa.
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
        'cosecha' => 'Servicios de cosecha',
        'siembra' => 'Servicios de siembra',
        'pulverizacion' => 'Pulverización / Aplicaciones',
        'semillas' => 'Semillas',
        'fertilizantes' => 'Fertilizantes',
        'fitosanitarios' => 'Fitosanitarios / Agroquímicos',
        'acopio_secado' => 'Acopio / Secado de granos',
        'analisis_suelos' => 'Análisis de suelos',
        'riego' => 'Riego',
        'veterinaria' => 'Servicios veterinarios',
        'reproduccion' => 'Reproducción / Inseminación',
        'compra_hacienda' => 'Compra de hacienda',
        'pasturas_forrajes' => 'Pasturas / Forrajes',
        'suplementos' => 'Suplementos / Balanceados',
        'alambrados' => 'Alambrados / Corrales',
        'agua_bebederos' => 'Agua / Bebederos',
        'repuestos' => 'Repuestos',
        'neumaticos' => 'Neumáticos',
        'lubricantes' => 'Lubricantes',
        'maquinaria' => 'Maquinaria / Equipos',
        'construcciones' => 'Construcciones / Mejoras',
        'arrendamientos' => 'Arrendamientos rurales',
        'seguros' => 'Seguros',
        'energia' => 'Energía eléctrica',
        'comunicaciones' => 'Telefonía / Internet',
        'servicios_contables' => 'Servicios contables',
        'servicios_legales' => 'Servicios legales',
        'impuestos_tasas' => 'Impuestos / Tasas',
        'gastos_bancarios' => 'Gastos bancarios / Financieros',
    ];

    const ACTIVIDADES = [
        'general' => 'General',
        'agricultura' => 'Agricultura',
        'ganaderia' => 'Ganadería',
        'feedlot' => 'Feedlot',
        'inversiones' => 'Inversiones',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'razon_social', 'cuit', 'rubro', 'actividad', 'telefono', 'email', 'activo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRubroLabelAttribute(): string
    {
        return Rubro::opciones($this->id_empresa)[$this->rubro] ?? ($this->rubro ?? '—');
    }

    public function getActividadLabelAttribute(): string
    {
        $actividad = $this->actividadPredeterminada();

        return self::ACTIVIDADES[$actividad] ?? ($actividad ?? '—');
    }

    public function scopePorCuit($query, string $cuit)
    {
        return $query->whereRaw("REPLACE(REPLACE(REPLACE(cuit, '-', ''), ' ', ''), '.', '') = ?", [self::normalizarCuit($cuit)]);
    }

    public static function normalizarCuit(string $cuit): string
    {
        return preg_replace('/[\s.\-]/', '', $cuit);
    }

    public function actividadPredeterminada(): ?string
    {
        return $this->actividad ?: Rubro::predeterminada($this->rubro ?? '', $this->id_empresa);
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
