<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GastoNoArca extends Model
{
    use UsaUuid;

    protected $table = 'gastos_no_arca';

    protected $fillable = [
        'id_empresa', 'nombre', 'categoria', 'rubro', 'zona', 'id_inmueble', 'orden', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    const CATEGORIAS = [
        'servicios_publicos' => 'Servicios públicos',
        'expensas' => 'Expensas',
        'obra_social' => 'Obra social / salud',
        'telecom' => 'Telecom / cable',
        'comite_cuenca' => 'Comité de cuenca',
        'seguros' => 'Seguros',
        'tarjetas' => 'Tarjetas de crédito',
        'rrhh' => 'Sueldos y RRHH',
        'combustible' => 'Combustible',
        'otros' => 'Otros',
    ];

    const RUBROS = [
        'servicios_agua' => 'Agua', 'alquileres' => 'Alquileres', 'combustible' => 'Combustible',
        'comite_cuenca' => 'Comité de cuenca', 'expensas' => 'Expensas', 'gas' => 'Gas',
        'energia' => 'Luz', 'mantenimiento' => 'Mantenimiento', 'obra_social' => 'Obra social / Salud',
        'otros' => 'Otros', 'seguros' => 'Seguros', 'servicios_contables' => 'Servicios contables',
        'empleados' => 'Sueldos y RRHH', 'tarjetas' => 'Tarjetas de crédito', 'comunicaciones' => 'Telefonía / Internet',
    ];

    const ZONAS = ['corrientes' => 'Corrientes', 'general' => 'General', 'santa_fe' => 'Santa Fe'];

    public static function zonaSugerida(string $nombre): string
    {
        return preg_match('/\bcorrientes\b(?!\s+\d)/', Str::lower(Str::ascii($nombre))) ? 'corrientes' : 'santa_fe';
    }

    public static function rubroSugerido(string $nombre, string $categoria): ?string
    {
        $nombre = Str::lower(Str::ascii($nombre));
        if (preg_match('/\bepe\b|empresa provincial (?:de (?:la )?)?energia/', $nombre)) {
            return 'energia';
        }
        if ($categoria === 'servicios_publicos') {
            if (preg_match('/\bluz\b|\benergia electrica\b/', $nombre)) {
                return 'energia';
            }
            if (preg_match('/\blitoral gas\b|\bgas\b/', $nombre)) {
                return 'gas';
            }
            if (preg_match('/\bagua(?:s)?\b|\bass?a\b/', $nombre)) {
                return 'servicios_agua';
            }
        }

        return ['obra_social' => 'obra_social', 'telecom' => 'comunicaciones', 'rrhh' => 'empleados',
            'seguros' => 'seguros', 'combustible' => 'combustible', 'expensas' => 'expensas',
            'tarjetas' => 'tarjetas', 'comite_cuenca' => 'comite_cuenca'][$categoria] ?? null;
    }

    protected static function booted(): void
    {
        static::creating(function (self $gasto) {
            $gasto->zona ??= self::zonaSugerida($gasto->nombre);
            $gasto->rubro ??= self::rubroSugerido($gasto->nombre, $gasto->categoria ?? 'otros');
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'id_inmueble');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoGastoNoArca::class, 'id_gasto');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
