<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoSanidad extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa;

    protected $table = 'eventos_sanidad';

    const TIPOS = [
        'vacunacion'      => 'Vacunación',
        'desparasitacion' => 'Desparasitación',
        'tratamiento'     => 'Tratamiento',
        'diagnostico'     => 'Diagnóstico',
        'castracion'      => 'Castración',
        'otro'            => 'Otro',
    ];

    protected $fillable = [
        'id_empresa',
        'id_establecimiento',
        'id_animal',
        'tipo_evento',
        'fecha',
        'producto',
        'dosis',
        'veterinario',
        'categoria_afectada',
        'cantidad_afectada',
        'observaciones',
    ];

    protected $casts = [
        'fecha'             => 'date',
        'cantidad_afectada' => 'integer',
    ];

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo_evento] ?? ucfirst($this->tipo_evento);
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
