<?php

namespace App\Traits;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait PerteneceAEmpresa
 *
 * Implementa el aislamiento multiempresa descripto en el Documento 07,
 * sección 3.3, y referenciado en el Documento 10, sección 3.2: toda consulta
 * sobre un modelo que use este trait filtra automáticamente por la empresa
 * del usuario autenticado, sin que cada desarrollador tenga que recordar
 * agregar ->where('id_empresa', ...) en cada consulta manualmente.
 *
 * Modelos que deben usar este trait (ver Documento 03, todas las tablas que
 * cuelgan directa o indirectamente de id_empresa): Establecimiento, Usuario,
 * UnidadManejo, Campaña, Animal, Insumo, Proveedor, Cliente, etc.
 *
 * USO:
 *   class Establecimiento extends Model
 *   {
 *       use PerteneceAEmpresa;
 *       ...
 *   }
 *
 * A partir de ahí, Establecimiento::all() devuelve únicamente los
 * establecimientos de la empresa del usuario logueado, sin código adicional
 * en el controlador o componente Livewire que lo consulte.
 */
trait PerteneceAEmpresa
{
    /**
     * El "boot" de un trait en Eloquent se ejecuta una sola vez al arrancar
     * el modelo, y es el lugar correcto para registrar un Global Scope.
     */
    protected static function bootPerteneceAEmpresa(): void
    {
        // No aplicar el scope en consola (comandos artisan, seeders, tinker)
        // salvo que se fuerce explícitamente, porque en esos contextos puede
        // no haber un usuario autenticado del que tomar la empresa, y un
        // seeder legítimamente necesita poder crear datos de cualquier empresa.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        static::addGlobalScope('empresa', function (Builder $builder) {
            if (auth()->check() && auth()->user()->id_empresa) {
                $builder->where(
                    $builder->getModel()->getTable().'.id_empresa',
                    auth()->user()->id_empresa
                );
            }
        });

        // Al crear un registro nuevo, completar automáticamente id_empresa
        // con la empresa del usuario autenticado, para no tener que pasarlo
        // a mano en cada Livewire::component o Controller que da de alta algo.
        static::creating(function (Model $model) {
            if (auth()->check() && empty($model->id_empresa)) {
                $model->id_empresa = auth()->user()->id_empresa;
            }
        });
    }

    /**
     * Scope local explícito para los casos donde sí se necesita consultar
     * a través de empresas (por ejemplo, un panel de soporte interno de
     * Nakama que diera servicio a más de un cliente con este mismo sistema,
     * escenario contemplado pero no activo en el MVP de esta empresa,
     * ver Documento 03, criterios de diseño, sección 1).
     */
    public function scopeSinFiltroDeEmpresa(Builder $query): Builder
    {
        return $query->withoutGlobalScope('empresa');
    }

    /**
     * Relación inversa: toda entidad que pertenece a una empresa puede
     * acceder a ella vía $modelo->empresa.
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }
}
