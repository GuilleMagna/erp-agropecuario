<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoReproduccion extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa;

    protected $table = 'eventos_reproduccion';

    const TIPOS = [
        'servicio'          => 'Servicio natural',
        'inseminacion'      => 'Inseminación artificial',
        'diagnostico_prenez'=> 'Diagnóstico de preñez',
        'parto'             => 'Parto',
        'destete'           => 'Destete',
    ];

    const RESULTADOS = [
        'prenada'        => 'Preñada',
        'vacia'          => 'Vacía',
        'parto_simple'   => 'Parto simple',
        'parto_gemelar'  => 'Parto gemelar',
        'aborto'         => 'Aborto',
    ];

    protected $fillable = [
        'id_empresa',
        'id_establecimiento',
        'id_animal',
        'tipo_evento',
        'fecha',
        'resultado',
        'toro_caravana',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo_evento] ?? ucfirst($this->tipo_evento);
    }

    public function getResultadoLabelAttribute(): string
    {
        return self::RESULTADOS[$this->resultado] ?? ucfirst($this->resultado ?? '');
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'id_animal');
    }
}
