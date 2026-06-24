<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Empresa
 *
 * Raíz de toda la jerarquía multiempresa (Documento 03, sección 4.1).
 * Aunque el MVP de esta empresa opera con un único registro en esta tabla,
 * todas las demás entidades relevantes cuelgan de aquí mediante el trait
 * PerteneceAEmpresa, de forma que escalar a multiempresa en el futuro no
 * requiera tocar el resto del modelo (ver Documento 07, sección 3.3).
 *
 * NOTA: las relaciones hacia Establecimiento y Usuario son referencias
 * anticipadas a modelos que se construyen en la próxima sesión de trabajo
 * (módulo de Administración, Documento 02, módulo 1). No producen error de
 * carga porque PHP resuelve las clases dentro de un método recién cuando
 * ese método se invoca, no al cargar este archivo.
 *
 * @property string $id
 * @property string $razon_social
 * @property string $cuit
 * @property string $condicion_fiscal
 * @property string|null $domicilio_fiscal
 * @property string|null $logo_url
 * @property string $moneda_default
 * @property bool $activa
 */
class Empresa extends Model
{
    use HasFactory, UsaUuid;

    protected $table = 'empresas';

    protected $fillable = [
        'razon_social',
        'cuit',
        'condicion_fiscal',
        'domicilio_fiscal',
        'logo_url',
        'moneda_default',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function establecimientos(): HasMany
    {
        return $this->hasMany(Establecimiento::class, 'id_empresa');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_empresa');
    }
}
