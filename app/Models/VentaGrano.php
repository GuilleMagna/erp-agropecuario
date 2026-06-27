<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VentaGrano extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'ventas_granos';

    protected $fillable = [
        'id_establecimiento', 'id_campana',
        'comprador', 'cuit_comprador',
        'cereal', 'tipo_venta', 'corredor', 'numero_comprobante',
        'fecha', 'fecha_entrega',
        'cantidad_tn', 'precio_tn', 'moneda', 'importe_total',
        'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha'         => 'date',
        'fecha_entrega' => 'date',
        'cantidad_tn'   => 'decimal:3',
        'precio_tn'     => 'decimal:2',
        'importe_total' => 'decimal:2',
    ];

    const CEREALES = [
        'soja'     => 'Soja',
        'maiz'     => 'Maíz',
        'trigo'    => 'Trigo',
        'girasol'  => 'Girasol',
        'sorgo'    => 'Sorgo',
        'cebada'   => 'Cebada',
        'avena'    => 'Avena',
        'colza'    => 'Colza',
        'otro'     => 'Otro',
    ];

    const TIPOS_VENTA = [
        'disponible'  => 'Disponible',
        'forward'     => 'Forward',
        'a_fijar'     => 'A fijar',
        'canje'       => 'Canje',
        'exportacion' => 'Exportación directa',
    ];

    const MONEDAS = [
        'USD' => 'Dólares (USD)',
        'ARS' => 'Pesos (ARS)',
    ];

    const ESTADOS = [
        'borrador'   => 'Borrador',
        'confirmada' => 'Confirmada',
        'cobrada'    => 'Cobrada',
        'cancelada'  => 'Cancelada',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['cereal', 'tipo_venta', 'fecha', 'cantidad_tn', 'precio_tn', 'moneda', 'importe_total', 'estado'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getCerealLabelAttribute(): string
    {
        return self::CEREALES[$this->cereal] ?? $this->cereal;
    }

    public function getTipoVentaLabelAttribute(): string
    {
        return self::TIPOS_VENTA[$this->tipo_venta] ?? $this->tipo_venta;
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function campana()
    {
        return $this->belongsTo(Campana::class, 'id_campana');
    }
}
