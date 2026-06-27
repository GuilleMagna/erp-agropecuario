<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Siembra extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'siembras';

    const CULTIVOS = [
        'soja'     => 'Soja',
        'maiz'     => 'Maíz',
        'trigo'    => 'Trigo',
        'girasol'  => 'Girasol',
        'sorgo'    => 'Sorgo',
        'cebada'   => 'Cebada',
        'avena'    => 'Avena',
        'colza'    => 'Colza / Canola',
        'algodon'  => 'Algodón',
        'mani'     => 'Maní',
        'arroz'    => 'Arroz',
        'pasturas' => 'Pasturas',
        'verdeos'  => 'Verdeos',
        'otro'     => 'Otro',
    ];

    const ESTADOS = [
        'planificada' => 'Planificada',
        'sembrada'    => 'Sembrada',
        'en_cultivo'  => 'En cultivo',
        'cosechada'   => 'Cosechada',
        'perdida'     => 'Perdida',
    ];

    protected $fillable = [
        'id_empresa',
        'id_campana',
        'id_lote',
        'cultivo',
        'variedad',
        'fecha_siembra',
        'superficie_sembrada_ha',
        'densidad_siembra',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_siembra'          => 'date',
        'superficie_sembrada_ha' => 'float',
        'densidad_siembra'       => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['cultivo', 'variedad', 'fecha_siembra', 'superficie_sembrada_ha', 'estado', 'id_campana', 'id_lote'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getCultivoLabelAttribute(): string
    {
        return self::CULTIVOS[$this->cultivo] ?? ucfirst($this->cultivo);
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class, 'id_campana');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'id_lote');
    }

    public function labores(): HasMany
    {
        return $this->hasMany(Labor::class, 'id_siembra');
    }

    public function cosechas(): HasMany
    {
        return $this->hasMany(Cosecha::class, 'id_siembra');
    }
}
