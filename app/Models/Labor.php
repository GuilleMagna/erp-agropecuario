<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Labor extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'labores';

    const TIPOS = [
        'pulverizacion'  => 'Pulverización',
        'fertilizacion'  => 'Fertilización',
        'rastra'         => 'Rastra',
        'arado'          => 'Arado',
        'subsolado'      => 'Subsolado',
        'riego'          => 'Riego',
        'desmalezado'    => 'Desmalezado',
        'control_plagas' => 'Control de plagas',
        'monitoreo'      => 'Monitoreo',
        'transporte'     => 'Transporte',
        'otro'           => 'Otro',
    ];

    protected $fillable = [
        'id_empresa',
        'id_campana',
        'id_lote',
        'id_siembra',
        'tipo_labor',
        'fecha',
        'descripcion',
        'superficie_trabajada_ha',
        'observaciones',
    ];

    protected $casts = [
        'fecha'                   => 'date',
        'superficie_trabajada_ha' => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo_labor', 'fecha', 'id_lote', 'id_siembra', 'superficie_trabajada_ha', 'descripcion'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getTipoLaborLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo_labor] ?? ucfirst($this->tipo_labor);
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class, 'id_campana');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'id_lote');
    }

    public function siembra(): BelongsTo
    {
        return $this->belongsTo(Siembra::class, 'id_siembra');
    }
}
