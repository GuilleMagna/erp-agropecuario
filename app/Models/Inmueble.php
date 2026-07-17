<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Propiedades de renta (departamentos alquilados), separadas de los
 * Establecimientos (campos productivos). No usa PerteneceAEmpresa: igual
 * que gastos_no_arca, no están asignadas a una empresa puntual de las 3
 * sociedades ARCA (id_empresa queda nullable para uso futuro).
 */
class Inmueble extends Model
{
    use UsaUuid;

    protected $table = 'inmuebles';

    protected $fillable = [
        'id_empresa', 'nombre', 'localidad', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function gastosNoArca(): HasMany
    {
        return $this->hasMany(GastoNoArca::class, 'id_inmueble');
    }

    public function ingresosAlquiler(): HasMany
    {
        return $this->hasMany(IngresoAlquiler::class, 'id_inmueble');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
