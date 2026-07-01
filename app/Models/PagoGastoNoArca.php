<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoGastoNoArca extends Model
{
    use UsaUuid;

    protected $table = 'pagos_gasto_no_arca';

    protected $fillable = [
        'id_gasto', 'mes', 'importe', 'fecha_pago', 'notas',
    ];

    protected $casts = [
        'mes'        => 'date',
        'fecha_pago' => 'date',
        'importe'    => 'decimal:2',
    ];

    public function gasto(): BelongsTo
    {
        return $this->belongsTo(GastoNoArca::class, 'id_gasto');
    }
}
