<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Movimiento extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'movimientos';

    const TIPOS = [
        'compra'                => 'Compra',
        'venta'                 => 'Venta',
        'nacimiento'            => 'Nacimiento',
        'muerte'                => 'Muerte',
        'faena'                 => 'Faena',
        'transferencia_entrada' => 'Transferencia (entrada)',
        'transferencia_salida'  => 'Transferencia (salida)',
    ];

    const TIPOS_POSITIVOS = ['compra', 'nacimiento', 'transferencia_entrada'];

    protected $fillable = [
        'id_empresa',
        'id_establecimiento',
        'tipo',
        'fecha',
        'categoria',
        'cantidad',
        'peso_total_kg',
        'precio_cabeza',
        'importe_total',
        'procedencia_destino',
        'observaciones',
    ];

    protected $casts = [
        'fecha'          => 'date',
        'cantidad'       => 'integer',
        'peso_total_kg'  => 'float',
        'precio_cabeza'  => 'float',
        'importe_total'  => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo', 'fecha', 'categoria', 'cantidad', 'peso_total_kg', 'importe_total', 'procedencia_destino'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function esPositivo(): bool
    {
        return in_array($this->tipo, self::TIPOS_POSITIVOS);
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }
}
