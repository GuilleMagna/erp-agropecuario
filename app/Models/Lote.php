<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lote extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa;

    protected $table = 'lotes';

    protected $fillable = [
        'id_empresa',
        'id_establecimiento',
        'nombre',
        'codigo',
        'tipo',
        'superficie_ha',
        'latitud',
        'longitud',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'superficie_ha' => 'float',
        'latitud'       => 'float',
        'longitud'      => 'float',
        'activo'        => 'boolean',
    ];

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
