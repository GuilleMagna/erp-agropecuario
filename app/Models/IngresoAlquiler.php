<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngresoAlquiler extends Model
{
    use UsaUuid;

    protected $table = 'ingresos_alquiler';

    protected $fillable = [
        'id_inmueble', 'mes', 'importe', 'fecha_cobro', 'notas',
    ];

    protected $casts = [
        'mes'         => 'date',
        'importe'     => 'decimal:2',
        'fecha_cobro' => 'date',
    ];

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'id_inmueble');
    }
}
