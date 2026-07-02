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
            if (auth()->check()) {
                $empresaId = self::resolverEmpresaActiva();
                if ($empresaId) {
                    $builder->where(
                        $builder->getModel()->getTable().'.id_empresa',
                        $empresaId
                    );
                }
            }
        });

        // Al crear un registro nuevo, completar automáticamente id_empresa.
        static::creating(function (Model $model) {
            if (auth()->check() && empty($model->id_empresa)) {
                $model->id_empresa = self::resolverEmpresaActiva();
            }
        });
    }

    /**
     * Devuelve el UUID de la empresa activa para el request actual.
     * Para administrador_sistema usa la empresa elegida en sesión si la hay;
     * para el resto siempre usa la empresa asignada al usuario.
     */
    public static function resolverEmpresaActiva(): ?string
    {
        if (!auth()->check()) return null;

        $user = auth()->user();

        if ($user->hasRole('administrador_sistema')) {
            $enSesion = session('empresa_activa_id');
            if ($enSesion) return $enSesion;
        }

        return $user->id_empresa;
    }

    /**
     * Scope local para consultas que deben cruzar todas las empresas
     * (reportes consolidados, paneles de administración, etc.).
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
