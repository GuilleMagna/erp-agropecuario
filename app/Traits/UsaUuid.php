<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait UsaUuid
 *
 * Documento 03, sección 3 (Convenciones): toda clave primaria es UUID en
 * lugar de autoincremental, elegido deliberadamente por la posibilidad de
 * generar IDs en el dispositivo móvil sin conflicto, de cara a una futura
 * sincronización offline-first (Documento 06, sección 1.2) — un ID generado
 * en el celular sin conexión nunca puede chocar con uno generado en el
 * servidor, cosa que sí podría pasar con autoincrementales.
 *
 * Todo modelo nuevo del proyecto debe usar este trait en lugar de repetir
 * la lógica de generación de UUID en su propio boot().
 *
 * USO:
 *   class Establecimiento extends Model
 *   {
 *       use UsaUuid, PerteneceAEmpresa;
 *       ...
 *   }
 */
trait UsaUuid
{
    public static function bootUsaUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function initializeUsaUuid(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }
}
