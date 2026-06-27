<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Compra extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'compras';

    protected $fillable = [
        'id_proveedor', 'id_establecimiento',
        'tipo_comprobante', 'numero_comprobante',
        'fecha', 'fecha_vencimiento', 'estado',
        'subtotal', 'iva_porc', 'iva_importe', 'total',
        'stock_registrado', 'observaciones',
    ];

    protected $casts = [
        'fecha'             => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal'          => 'decimal:2',
        'iva_porc'          => 'decimal:2',
        'iva_importe'       => 'decimal:2',
        'total'             => 'decimal:2',
        'stock_registrado'  => 'boolean',
    ];

    const TIPOS_COMPROBANTE = [
        'factura_a' => 'Factura A',
        'factura_b' => 'Factura B',
        'factura_c' => 'Factura C',
        'remito'    => 'Remito',
        'recibo'    => 'Recibo',
        'ticket'    => 'Ticket',
        'otro'      => 'Otro',
    ];

    const ESTADOS = [
        'pendiente'  => 'Pendiente',
        'recibida'   => 'Recibida',
        'pagada'     => 'Pagada',
        'cancelada'  => 'Cancelada',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo_comprobante', 'numero_comprobante', 'fecha', 'estado', 'subtotal', 'iva_importe', 'total', 'id_proveedor'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getTipoComprobanteLabelAttribute(): string
    {
        return self::TIPOS_COMPROBANTE[$this->tipo_comprobante] ?? $this->tipo_comprobante;
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function items()
    {
        return $this->hasMany(CompraItem::class, 'id_compra');
    }
}
