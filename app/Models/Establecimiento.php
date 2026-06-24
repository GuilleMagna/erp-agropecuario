<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Establecimiento
 *
 * Representa cada campo físico de la empresa (Documento 03, sección 4.2).
 * Es uno de los puntos de anclaje más usados del sistema: casi todas las
 * consultas y reportes filtran por establecimiento.
 *
 * @property string $id
 * @property string $id_empresa
 * @property string $nombre
 * @property string|null $provincia
 * @property string|null $partido_departamento
 * @property string|null $localidad
 * @property float|null $latitud
 * @property float|null $longitud
 * @property float|null $superficie_total_ha
 * @property float|null $superficie_agricola_ha
 * @property float|null $superficie_ganadera_ha
 * @property string $tipo_tenencia
 * @property string|null $partida_catastral
 * @property string|null $responsable_id
 * @property bool $activo
 */
class Establecimiento extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa;

    protected $table = 'establecimientos';

    protected $fillable = [
        'id_empresa',
        'nombre',
        'provincia',
        'partido_departamento',
        'localidad',
        'latitud',
        'longitud',
        'superficie_total_ha',
        'superficie_agricola_ha',
        'superficie_ganadera_ha',
        'tipo_tenencia',
        'partida_catastral',
        'responsable_id',
        'activo',
    ];

    protected $casts = [
        'latitud'               => 'float',
        'longitud'              => 'float',
        'superficie_total_ha'   => 'float',
        'superficie_agricola_ha'=> 'float',
        'superficie_ganadera_ha'=> 'float',
        'activo'                => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    /** Usuarios que tienen acceso a este establecimiento (N:M). */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            Usuario::class,
            'usuarios_establecimientos',
            'id_establecimiento',
            'id_usuario'
        );
    }

    /** Lotes y potreros del establecimiento (Documento 03, sección 5.1). */
    public function unidadesManejo(): HasMany
    {
        return $this->hasMany(UnidadManejo::class, 'id_establecimiento');
    }

    // -------------------------------------------------------------------------
    // Scopes locales
    // -------------------------------------------------------------------------

    /** Solo establecimientos activos (uso frecuente en listados y dropdowns). */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
