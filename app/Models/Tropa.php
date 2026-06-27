<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tropa extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa;

    protected $table = 'tropas';

    const CATEGORIAS = [
        'novillo'    => 'Novillo',
        'novillito'  => 'Novillito',
        'vaquillona' => 'Vaquillona',
        'ternero'    => 'Ternero',
        'ternera'    => 'Ternera',
        'vaca'       => 'Vaca',
        'toro'       => 'Toro',
        'torito'     => 'Torito',
        'otro'       => 'Otro',
    ];

    const ESTADOS = [
        'activa'     => 'Activa',
        'finalizada' => 'Finalizada',
        'cancelada'  => 'Cancelada',
    ];

    protected $fillable = [
        'id_empresa',
        'id_corral',
        'id_establecimiento',
        'nombre',
        'categoria',
        'cantidad_cabezas',
        'fecha_entrada',
        'fecha_salida_estimada',
        'fecha_salida_real',
        'peso_promedio_entrada_kg',
        'peso_promedio_salida_kg',
        'objetivo_ganancia_diaria_kg',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_entrada'            => 'date',
        'fecha_salida_estimada'    => 'date',
        'fecha_salida_real'        => 'date',
        'cantidad_cabezas'         => 'integer',
        'peso_promedio_entrada_kg' => 'decimal:2',
        'peso_promedio_salida_kg'  => 'decimal:2',
        'objetivo_ganancia_diaria_kg' => 'decimal:3',
    ];

    public function corral(): BelongsTo
    {
        return $this->belongsTo(Corral::class, 'id_corral');
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function consumosAlimento(): HasMany
    {
        return $this->hasMany(ConsumoAlimento::class, 'id_tropa');
    }

    public function getCategoriaLabelAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? ucfirst($this->categoria ?? '');
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado ?? '');
    }

    public function getDiasEnFeedlotAttribute(): int
    {
        if (!$this->fecha_entrada) return 0;
        $fin = match ($this->estado) {
            'finalizada' => $this->fecha_salida_real ?? now(),
            'cancelada'  => $this->fecha_salida_real ?? $this->updated_at ?? now(),
            default      => now(),
        };
        return max(0, (int) $this->fecha_entrada->diffInDays($fin));
    }

    public function getGananciaTotalKgAttribute(): ?float
    {
        if (!$this->peso_promedio_entrada_kg || !$this->peso_promedio_salida_kg) return null;
        return (float) $this->peso_promedio_salida_kg - (float) $this->peso_promedio_entrada_kg;
    }

    public function getGananciaDiariaRealKgAttribute(): ?float
    {
        $total = $this->ganancia_total_kg;
        $dias  = $this->dias_en_feedlot;
        if ($total === null || $dias <= 0) return null;
        return round($total / $dias, 3);
    }

    public function getPesoEstimadoActualKgAttribute(): ?float
    {
        if (!$this->peso_promedio_entrada_kg) return null;
        if ($this->estado !== 'activa' || !$this->objetivo_ganancia_diaria_kg) return null;
        return round(
            (float) $this->peso_promedio_entrada_kg + ($this->objetivo_ganancia_diaria_kg * $this->dias_en_feedlot),
            2
        );
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }
}
