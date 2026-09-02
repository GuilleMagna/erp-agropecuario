<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VentaGrano extends Model
{
    use HasFactory, LogsActivity, PerteneceAEmpresa, UsaUuid;

    protected $table = 'ventas_granos';

    protected $fillable = [
        'id_establecimiento', 'id_campana',
        'comprador', 'id_comprador', 'cuit_comprador',
        'cereal', 'tipo_venta', 'corredor', 'numero_comprobante',
        'fecha', 'fecha_entrega',
        'cantidad_tn', 'precio_tn', 'moneda', 'importe_total',
        'cantidad_kg', 'unidad_cantidad', 'factor', 'precio_kg', 'precio_kg_es_neto', 'flete_kg',
        'deducciones', 'iva_deducciones', 'bonificacion',
        'ret_ganancias', 'ret_iva', 'iva_rg4310', 'debito_fiscal',
        'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_entrega' => 'date',
        'cantidad_tn' => 'decimal:3',
        'precio_tn' => 'decimal:2',
        'importe_total' => 'decimal:2',
        'cantidad_kg' => 'decimal:2',
        'factor' => 'decimal:2',
        'precio_kg' => 'decimal:4',
        'precio_kg_es_neto' => 'boolean',
        'flete_kg' => 'decimal:4',
        'deducciones' => 'decimal:2',
        'iva_deducciones' => 'decimal:2',
        'bonificacion' => 'decimal:2',
        'ret_ganancias' => 'decimal:2',
        'ret_iva' => 'decimal:2',
        'iva_rg4310' => 'decimal:2',
        'debito_fiscal' => 'decimal:2',
    ];

    const CEREALES = [
        'soja' => 'Soja',
        'maiz' => 'Maíz',
        'trigo' => 'Trigo',
        'girasol' => 'Girasol',
        'sorgo' => 'Sorgo',
        'cebada' => 'Cebada',
        'avena' => 'Avena',
        'colza' => 'Colza',
        'otro' => 'Otro',
    ];

    const TIPOS_VENTA = [
        'disponible' => 'Disponible',
        'forward' => 'Forward',
        'a_fijar' => 'A fijar',
        'canje' => 'Canje',
        'exportacion' => 'Exportación directa',
    ];

    const MONEDAS = [
        'USD' => 'Dólares (USD)',
        'ARS' => 'Pesos (ARS)',
    ];

    const ESTADOS = [
        'borrador' => 'Borrador',
        'confirmada' => 'Confirmada',
        'cobrada' => 'Cobrada',
        'cancelada' => 'Cancelada',
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

    /**
     * Réplica de la columna "Subtotal" de la hoja VENTAS del Excel:
     * El precio por KG guardado ya es neto de flete.
     */
    public function getSubtotalAttribute(): ?float
    {
        if ($this->cantidad_kg === null || $this->precio_kg === null) {
            return null;
        }

        $factor = (float) ($this->factor ?? 100);

        $precioNetoKg = $this->precio_kg_es_neto
            ? (float) $this->precio_kg
            : (float) $this->precio_kg - (float) ($this->flete_kg ?? 0);

        return (float) $this->cantidad_kg * ($factor / 100) * $precioNetoKg;
    }

    /** Réplica de "Total Reten. AFIP": =+Ret.Gan.+Ret.IVA */
    public function getTotalRetencionesAfipAttribute(): ?float
    {
        return (float) ($this->ret_ganancias ?? 0) + (float) ($this->ret_iva ?? 0);
    }

    /** Réplica de "Resultado IVA": =+Subtotal*0.105-IVA_deducciones */
    public function getResultadoIvaAttribute(): ?float
    {
        if ($this->subtotal === null) {
            return null;
        }

        return $this->subtotal * 0.105 - (float) ($this->iva_deducciones ?? 0);
    }

    /**
     * Importe neto visible en el listado. En las ventas nuevas el importe
     * guardado representa el Total Operación y las deducciones van aparte.
     * Los registros históricos ya tenían las deducciones descontadas.
     */
    public function getImporteListadoAttribute(): float
    {
        if (! $this->precio_kg_es_neto) {
            return (float) $this->importe_total;
        }

        return (float) $this->importe_total - (float) ($this->deducciones ?? 0);
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

    public function compradorCatalogo(): BelongsTo
    {
        return $this->belongsTo(Comprador::class, 'id_comprador');
    }
}
