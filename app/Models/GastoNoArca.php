<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GastoNoArca extends Model
{
    use UsaUuid;

    protected $table = 'gastos_no_arca';

    protected $fillable = [
        'id_empresa', 'nombre', 'categoria', 'id_inmueble', 'orden', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden'  => 'integer',
    ];

    const CATEGORIAS = [
        'servicios_publicos' => 'Servicios públicos',
        'expensas'           => 'Expensas',
        'obra_social'        => 'Obra social / salud',
        'telecom'            => 'Telecom / cable',
        'comite_cuenca'      => 'Comité de cuenca',
        'seguros'            => 'Seguros',
        'tarjetas'           => 'Tarjetas de crédito',
        'rrhh'               => 'Sueldos y RRHH',
        'combustible'        => 'Combustible',
        'otros'              => 'Otros',
    ];

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
