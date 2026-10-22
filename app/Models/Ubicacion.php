<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Catálogo compartido por empresas y gastos generales; conserva los códigos históricos. */
class Ubicacion extends Model
{
    protected $table = 'ubicaciones';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'nombre', 'tipo', 'id_padre'];

    const TIPOS = ['provincia' => 'Provincia', 'ciudad' => 'Ciudad', 'zona' => 'Zona', 'campo' => 'Campo', 'otro' => 'Otro'];

    protected static function booted(): void
    {
        static::creating(function (self $ubicacion) {
            $ubicacion->id ??= 'u_'.Str::lower(Str::random(24));
        });
        static::saving(function (self $ubicacion) {
            if ($ubicacion->id_padre && (! self::whereKey($ubicacion->id_padre)->exists()
                || in_array($ubicacion->id_padre, self::descendientes($ubicacion->id ?? ''), true))) {
                throw ValidationException::withMessages(['id_padre' => 'La ubicación superior no es válida o genera un ciclo.']);
            }
            if (! array_key_exists($ubicacion->tipo, self::TIPOS)) {
                throw ValidationException::withMessages(['tipo' => 'Seleccioná un tipo válido.']);
            }
        });
        static::updated(function (self $ubicacion) {
            // El vínculo no crea otro campo al editar su nombre o moverlo.
            Establecimiento::withoutGlobalScope('empresa')->where('id_ubicacion', $ubicacion->id)
                ->update(['nombre' => $ubicacion->nombre, 'zona' => $ubicacion->id_padre]);
        });
    }

    public static function opciones(): array
    {
        // También permite ejecutar importaciones y pruebas anteriores a esta migración.
        if (! Schema::hasTable('ubicaciones')) {
            return Proveedor::ZONAS;
        }
        $nodos = self::orderBy('nombre')->get()->keyBy('id');
        $opciones = [];
        foreach ($nodos as $id => $nodo) {
            $partes = [$nodo->nombre];
            $vistos = [$id];
            while ($nodo->id_padre && isset($nodos[$nodo->id_padre]) && ! in_array($nodo->id_padre, $vistos, true)) {
                $nodo = $nodos[$nodo->id_padre];
                $vistos[] = $nodo->id;
                array_unshift($partes, $nodo->nombre);
            }
            $opciones[$id] = implode(' / ', $partes);
        }
        asort($opciones, SORT_NATURAL | SORT_FLAG_CASE);

        return $opciones;
    }

    public static function descendientes(string $id): array
    {
        $ids = [$id];
        if (Schema::hasTable('ubicaciones')) {
            $nodos = self::all(['id', 'id_padre']);
            do {
                $cantidad = count($ids);
                foreach ($nodos as $nodo) {
                    if (in_array($nodo->id_padre, $ids, true) && ! in_array($nodo->id, $ids, true)) {
                        $ids[] = $nodo->id;
                    }
                }
            } while (count($ids) !== $cantidad);
        }
        if (in_array('santa_fe', $ids, true)) {
            $ids[] = 'el_trebol'; // Alias anterior a la unificación de Santa Fe.
        }

        return array_values(array_unique($ids));
    }

    /** Cada gasto cae en una sola rama: el gráfico suma sin duplicar padres e hijos. */
    public static function agrupaciones(?string $superior = null): array
    {
        if (! Schema::hasTable('ubicaciones')) {
            return [];
        }
        $nodos = self::all(['id', 'id_padre'])->keyBy('id');
        $grupos = [];
        foreach ($nodos as $id => $nodo) {
            $vistos = [$id];
            while ($nodo->id_padre && $nodo->id_padre !== $superior && isset($nodos[$nodo->id_padre]) && ! in_array($nodo->id_padre, $vistos, true)) {
                $nodo = $nodos[$nodo->id_padre];
                $vistos[] = $nodo->id;
            }
            $grupos[$id] = $nodo->id;
        }

        return $grupos;
    }
}
