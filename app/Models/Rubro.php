<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{
    use PerteneceAEmpresa, UsaUuid;

    protected $fillable = ['id_empresa', 'codigo', 'nombre', 'actividades', 'actividad_default'];

    protected $casts = ['actividades' => 'array'];

    /** Mantener los códigos históricos conserva la clasificación de compras anteriores. */
    public static function catalogo(?string $empresaId = null): array
    {
        $catalogo = [];
        $agricolas = ['cosecha', 'siembra', 'pulverizacion', 'semillas', 'fertilizantes', 'fitosanitarios', 'acopio_secado', 'analisis_suelos', 'riego'];
        $ganaderos = ['sanidad', 'veterinaria', 'reproduccion', 'compra_hacienda', 'pasturas_forrajes', 'alimento', 'suplementos'];
        $inversiones = ['bien_capital', 'maquinaria', 'construcciones'];
        foreach (Proveedor::RUBROS as $codigo => $nombre) {
            $actividades = array_keys(Proveedor::ACTIVIDADES);
            $default = $codigo === 'otro' ? '' : 'general';
            if (in_array($codigo, $agricolas, true)) {
                $actividades = ['agricultura'];
                $default = 'agricultura';
            } elseif (in_array($codigo, $ganaderos, true)) {
                $actividades = ['ganaderia', 'feedlot'];
                $default = 'ganaderia';
            } elseif (in_array($codigo, $inversiones, true)) {
                $actividades = ['inversiones'];
                $default = 'inversiones';
            }
            $catalogo[$codigo] = ['nombre' => $nombre, 'actividades' => $actividades, 'actividad_default' => $default];
        }
        $empresaId ??= self::resolverEmpresaActiva();
        if ($empresaId) {
            foreach (self::withoutGlobalScope('empresa')->where('id_empresa', $empresaId)->get() as $rubro) {
                $catalogo[$rubro->codigo] = $rubro->only(['nombre', 'actividades', 'actividad_default']);
            }
        }

        return $catalogo;
    }

    public static function opciones(?string $empresaId = null): array
    {
        return array_map(fn ($rubro) => $rubro['nombre'], self::catalogo($empresaId));
    }

    public static function actividadesPermitidas(string $codigo, ?string $empresaId = null): array
    {
        return self::catalogo($empresaId)[$codigo]['actividades'] ?? array_keys(Proveedor::ACTIVIDADES);
    }

    public static function predeterminada(string $codigo, ?string $empresaId = null): ?string
    {
        // Los proveedores importados con "otro" siguen pendientes de clasificar.
        return (self::catalogo($empresaId)[$codigo]['actividad_default'] ?? null) ?: null;
    }
}
