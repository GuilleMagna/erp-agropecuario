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
    use HasFactory, LogsActivity, PerteneceAEmpresa, UsaUuid;

    protected $table = 'compras';

    protected $fillable = [
        'id_empresa',
        'id_proveedor', 'id_establecimiento',
        'tipo_comprobante', 'numero_comprobante',
        'fecha', 'fecha_vencimiento', 'estado',
        'subtotal', 'iva_porc', 'iva_importe', 'total',
        'stock_registrado', 'observaciones',
        'actividad', 'zona', 'rubro', 'id_lote', 'id_campana',
        'presentado_arca',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'iva_porc' => 'decimal:2',
        'iva_importe' => 'decimal:2',
        'total' => 'decimal:2',
        'stock_registrado' => 'boolean',
        'presentado_arca' => 'boolean',
    ];

    const TIPOS_COMPROBANTE = [
        'factura_a' => 'Factura A',
        'factura_b' => 'Factura B',
        'factura_c' => 'Factura C',
        'nota_credito' => 'Nota de crédito',
        'nota_debito' => 'Nota de débito',
        'liquidacion' => 'Liquidación',
        'remito' => 'Remito',
        'recibo' => 'Recibo',
        'ticket' => 'Ticket',
        'otro' => 'Otro',
    ];

    /** Las notas de crédito se guardan en negativo: restan del total de compras
     *  y del crédito fiscal, igual que en el libro IVA. */
    const TIPOS_NEGATIVOS = ['nota_credito'];

    const ESTADOS = [
        'pendiente' => 'Pendiente',
        'recibida' => 'Recibida',
        'pagada' => 'Pagada',
        'cancelada' => 'Cancelada',
    ];

    const ACTIVIDADES = [
        'agricultura' => 'Agricultura',
        'ganaderia' => 'Ganadería',
        'feedlot' => 'Feedlot',
        'general' => 'General',
        'inversiones' => 'Inversiones',
    ];

    /** Categorías base compartidas; Rubro agrega el catálogo propio de cada empresa. */
    const RUBROS = Proveedor::RUBROS;

    /** Mismas zonas que Establecimiento::ZONAS. Se autocompleta al elegir
     *  el establecimiento de la compra (ver GestionCompras). */
    const ZONAS = [
        'general' => 'General',
        'el_trebol' => 'El Trébol',
        'corrientes' => 'Corrientes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo_comprobante', 'numero_comprobante', 'fecha', 'estado', 'subtotal', 'iva_importe', 'total', 'id_proveedor', 'actividad', 'zona', 'rubro'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getTipoComprobanteLabelAttribute(): string
    {
        return self::TIPOS_COMPROBANTE[$this->tipo_comprobante] ?? $this->tipo_comprobante;
    }

    public function esNotaCredito(): bool
    {
        return in_array($this->tipo_comprobante, self::TIPOS_NEGATIVOS, true);
    }

    /** Comprobantes incluidos en lo que el contador presentó ante ARCA. */
    public function scopePresentados($query)
    {
        return $query->where('presentado_arca', true);
    }

    public function scopeNoPresentados($query)
    {
        return $query->where('presentado_arca', false);
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    /** Valores sugeridos; consultar no modifica la imputación guardada. */
    public function actividadSugerida(): ?string
    {
        if ($this->actividad) {
            return $this->actividad;
        }
        $proveedor = $this->proveedor;
        if ($proveedor && $proveedor->id_empresa !== $this->id_empresa) {
            $proveedor = null;
        }
        if ($this->rubro && $this->rubro !== 'otro' && $this->rubro !== $proveedor?->rubro) {
            return Rubro::predeterminada($this->rubro, $this->id_empresa);
        }

        return $proveedor?->actividadPredeterminada();
    }

    public function rubroSugerido(): ?string
    {
        if ($this->rubro && $this->rubro !== 'otro') {
            return $this->rubro;
        }
        $proveedor = $this->proveedor;

        return $proveedor && $proveedor->id_empresa === $this->id_empresa && $proveedor->rubro !== 'otro'
            ? $proveedor->rubro : $this->rubro;
    }

    public function getActividadLabelAttribute(): string
    {
        return self::ACTIVIDADES[$this->actividad] ?? ($this->actividad ?? '—');
    }

    public function getRubroLabelAttribute(): string
    {
        return Rubro::opciones($this->id_empresa)[$this->rubro] ?? ($this->rubro ?? '—');
    }

    public function getZonaLabelAttribute(): string
    {
        return self::ZONAS[$this->zona] ?? ($this->zona ?? '—');
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

    public function lote()
    {
        return $this->belongsTo(Lote::class, 'id_lote');
    }

    public function campana()
    {
        return $this->belongsTo(Campana::class, 'id_campana');
    }
}
