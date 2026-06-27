<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cosecha extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'cosechas';

    protected $fillable = [
        'id_empresa',
        'id_siembra',
        'fecha_cosecha',
        'superficie_cosechada_ha',
        'rinde_kg_ha',
        'humedad_porc',
        'produccion_total_kg',
        'observaciones',
    ];

    protected $casts = [
        'fecha_cosecha'           => 'date',
        'superficie_cosechada_ha' => 'float',
        'rinde_kg_ha'             => 'float',
        'humedad_porc'            => 'float',
        'produccion_total_kg'     => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fecha_cosecha', 'superficie_cosechada_ha', 'rinde_kg_ha', 'humedad_porc', 'produccion_total_kg'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function siembra(): BelongsTo
    {
        return $this->belongsTo(Siembra::class, 'id_siembra');
    }
}
