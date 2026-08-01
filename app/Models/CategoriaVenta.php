<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaVenta extends Model
{
    use HasFactory, UsaUuid;

    protected $table = 'categorias_venta';

    protected $fillable = [
        'nombre', 'tipo_cantidad', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    const TIPOS_CANTIDAD = [
        'animales_kg' => 'Cantidad de animales / KG',
        'quintales' => 'Quintales',
    ];

    public function compradores(): HasMany
    {
        return $this->hasMany(Comprador::class, 'id_categoria_venta');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
