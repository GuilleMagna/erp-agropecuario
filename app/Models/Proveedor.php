<?php

namespace App\Models;

use App\Services\SincronizarRubroCuit;
use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Proveedor extends Model
{
    use HasFactory, LogsActivity, PerteneceAEmpresa, UsaUuid;

    protected $table = 'proveedores';

    protected $fillable = [
        'id_empresa',
        'nombre', 'razon_social', 'cuit', 'rubro', 'actividad',
        'telefono', 'email', 'direccion', 'ciudad', 'provincia', 'zona',
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

    const ZONAS = ['corrientes' => 'Corrientes', 'general' => 'General', 'santa_fe' => 'Santa Fe'];

    public static function inferirZona(?string $provincia, ?string $ciudad = null, ?string $direccion = null): string
    {
        $provincia = Str::lower(Str::ascii(trim($provincia ?? '')));
        if (preg_match('/\bcorrientes\b/', $provincia)) {
            return 'corrientes';
        }
        if (preg_match('/\bsanta\s+fe\b/', $provincia)) {
            return 'santa_fe';
        }
        $direccion = Str::lower(Str::ascii($direccion ?? ''));
        // Evitar confundir la calle Corrientes con la provincia.
        if (preg_match('/(?:provincia\s+(?:de\s+)?|prov[.:]\s*)corrientes\b|[,(-]\s*corrientes\s*\)?\s*$/', $direccion)
            || Str::lower(trim($ciudad ?? '')) === 'corrientes') {
            return 'corrientes';
        }

        return 'santa_fe';
    }

    public function zonaPredeterminada(): string
    {
        return array_key_exists($this->zona ?? '', self::ZONAS)
            ? $this->zona : self::inferirZona($this->provincia, $this->ciudad, $this->direccion);
    }

    protected static function booted(): void
    {
        static::creating(function (self $proveedor) {
            if ((! $proveedor->rubro || $proveedor->rubro === 'otro') && self::normalizarCuit($proveedor->cuit ?? '')) {
                $existente = self::withoutGlobalScope('empresa')->porCuit($proveedor->cuit)
                    ->where('id_empresa', '!=', $proveedor->id_empresa)->whereNotNull('rubro')->whereNotIn('rubro', ['', 'otro'])
                    ->orderByDesc('updated_at')->orderBy('id')->first();
                if ($existente) {
                    SincronizarRubroCuit::asegurarCatalogo($existente->rubro, $existente->id_empresa, $proveedor->id_empresa);
                    $proveedor->rubro = $existente->rubro;
                }
            }
            $proveedor->zona ??= self::inferirZona($proveedor->provincia, $proveedor->ciudad, $proveedor->direccion);
        });
    }

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
