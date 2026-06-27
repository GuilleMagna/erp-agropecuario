<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumoAlimento extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa;

    protected $table = 'consumos_alimento';

    protected $fillable = [
        'id_empresa',
        'id_corral',
        'id_tropa',
        'id_establecimiento',
        'id_insumo',
        'fecha',
        'descripcion_alimento',
        'cantidad_kg',
        'costo_unitario',
        'costo_total',
        'observaciones',
    ];

    protected $casts = [
        'fecha'          => 'date',
        'cantidad_kg'    => 'decimal:2',
        'costo_unitario' => 'decimal:4',
        'costo_total'    => 'decimal:2',
    ];

    public function corral(): BelongsTo
    {
        return $this->belongsTo(Corral::class, 'id_corral');
    }

    public function tropa(): BelongsTo
    {
        return $this->belongsTo(Tropa::class, 'id_tropa');
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'id_insumo');
    }

    public function getNombreAlimentoAttribute(): string
    {
        return $this->insumo?->nombre ?? $this->descripcion_alimento ?? '—';
    }
}
