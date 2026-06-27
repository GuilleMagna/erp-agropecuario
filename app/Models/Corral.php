<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Corral extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa;

    protected $table = 'corrales';

    protected $fillable = [
        'id_empresa',
        'id_establecimiento',
        'nombre',
        'codigo',
        'capacidad_cabezas',
        'superficie_m2',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'capacidad_cabezas' => 'integer',
        'superficie_m2'     => 'float',
        'activo'            => 'boolean',
    ];

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function tropas(): HasMany
    {
        return $this->hasMany(Tropa::class, 'id_corral');
    }

    public function tropasActivas(): HasMany
    {
        return $this->hasMany(Tropa::class, 'id_corral')->where('estado', 'activa');
    }

    public function consumosAlimento(): HasMany
    {
        return $this->hasMany(ConsumoAlimento::class, 'id_corral');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function getOcupacionActualAttribute(): int
    {
        return $this->tropasActivas()->sum('cantidad_cabezas');
    }
}
