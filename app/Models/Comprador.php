<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comprador extends Model
{
    use HasFactory, UsaUuid;

    protected $table = 'compradores';

    protected $fillable = [
        'nombre', 'cuit', 'id_categoria_venta', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function categoriaVenta(): BelongsTo
    {
        return $this->belongsTo(CategoriaVenta::class, 'id_categoria_venta');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Busca un comprador por nombre (sin distinguir mayúsculas/espacios) dentro
     * de una categoría, o lo crea si no existe. Usado por los formularios de
     * ventas para no perder los compradores ya tipeados como texto libre.
     */
    public static function firstOrCreateParaCategoria(string $nombre, ?string $idCategoriaVenta): self
    {
        $nombreNormalizado = trim($nombre);

        $existente = static::query()
            ->when($idCategoriaVenta, fn ($q) => $q->where('id_categoria_venta', $idCategoriaVenta))
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombreNormalizado)])
            ->first();

        if ($existente) {
            return $existente;
        }

        return static::create([
            'nombre' => $nombreNormalizado,
            'id_categoria_venta' => $idCategoriaVenta,
            'activo' => true,
        ]);
    }
}
