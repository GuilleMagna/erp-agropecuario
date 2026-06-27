<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VentaHacienda extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'ventas_hacienda';

    protected $fillable = [
        'id_establecimiento',
        'comprador', 'corredor_feria', 'numero_guia',
        'fecha', 'tipo_operacion', 'categoria',
        'cantidad_cabezas', 'peso_promedio_kg', 'peso_total_kg',
        'precio_kg', 'precio_cabeza',
        'importe_total', 'moneda', 'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'cantidad_cabezas'=> 'integer',
        'peso_promedio_kg'=> 'decimal:2',
        'peso_total_kg'   => 'decimal:2',
        'precio_kg'       => 'decimal:4',
        'precio_cabeza'   => 'decimal:2',
        'importe_total'   => 'decimal:2',
    ];

    const TIPOS_OPERACION = [
        'invernada'     => 'Invernada',
        'terminado'     => 'Terminado / Engordado',
        'faena_directa' => 'Faena directa',
        'remate_feria'  => 'Remate / Feria',
        'consignacion'  => 'Consignación',
    ];

    const CATEGORIAS = [
        'toro'       => 'Toro',
        'vaca'       => 'Vaca',
        'novillo'    => 'Novillo',
        'novillito'  => 'Novillito',
        'vaquillona' => 'Vaquillona',
        'ternero'    => 'Ternero',
        'ternera'    => 'Ternera',
        'capon'      => 'Capón',
        'otro'       => 'Otro',
    ];

    const MONEDAS = [
        'ARS' => 'Pesos (ARS)',
        'USD' => 'Dólares (USD)',
    ];

    const ESTADOS = [
        'confirmada' => 'Confirmada',
        'cobrada'    => 'Cobrada',
        'cancelada'  => 'Cancelada',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo_operacion', 'categoria', 'fecha', 'cantidad_cabezas', 'peso_total_kg', 'importe_total', 'moneda', 'estado'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getCategoriaLabelAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria;
    }

    public function getTipoOperacionLabelAttribute(): string
    {
        return self::TIPOS_OPERACION[$this->tipo_operacion] ?? $this->tipo_operacion;
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }
}
