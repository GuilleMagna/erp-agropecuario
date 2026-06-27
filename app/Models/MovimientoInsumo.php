<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MovimientoInsumo extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'movimientos_insumos';

    protected $fillable = [
        'id_insumo', 'id_establecimiento', 'tipo', 'motivo',
        'cantidad', 'precio_unitario', 'importe_total',
        'fecha', 'numero_remito', 'proveedor', 'observaciones',
    ];

    protected $casts = [
        'cantidad'        => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'importe_total'   => 'decimal:2',
        'fecha'           => 'date',
    ];

    const TIPOS = [
        'entrada'          => 'Entrada',
        'salida'           => 'Salida',
        'ajuste_positivo'  => 'Ajuste +',
        'ajuste_negativo'  => 'Ajuste −',
    ];

    const TIPOS_POSITIVOS = ['entrada', 'ajuste_positivo'];

    const MOTIVOS = [
        'compra'             => 'Compra',
        'consumo_campo'      => 'Consumo en campo',
        'transferencia'      => 'Transferencia',
        'devolucion'         => 'Devolución',
        'merma'              => 'Merma / Pérdida',
        'conteo_inventario'  => 'Conteo de inventario',
        'otro'               => 'Otro',
    ];

    const MOTIVOS_POR_TIPO = [
        'entrada'         => ['compra', 'devolucion', 'transferencia', 'conteo_inventario', 'otro'],
        'salida'          => ['consumo_campo', 'transferencia', 'merma', 'conteo_inventario', 'otro'],
        'ajuste_positivo' => ['conteo_inventario', 'otro'],
        'ajuste_negativo' => ['merma', 'conteo_inventario', 'otro'],
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo', 'motivo', 'id_insumo', 'id_establecimiento', 'cantidad', 'precio_unitario', 'importe_total', 'fecha'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getMotiveLabelAttribute(): string
    {
        return self::MOTIVOS[$this->motivo] ?? $this->motivo;
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'id_insumo');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }
}
