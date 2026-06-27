<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campana extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa;

    protected $table = 'campanas';

    protected $fillable = [
        'id_empresa',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function siembras(): HasMany
    {
        return $this->hasMany(Siembra::class, 'id_campana');
    }

    public function labores(): HasMany
    {
        return $this->hasMany(Labor::class, 'id_campana');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
