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

class Animal extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'animales';

    const RAZAS = [
        'angus'     => 'Angus',
        'hereford'  => 'Hereford',
        'braford'   => 'Braford',
        'brangus'   => 'Brangus',
        'shorthorn' => 'Shorthorn',
        'limousin'  => 'Limousin',
        'simmental' => 'Simmental',
        'holstein'  => 'Holstein',
        'jersey'    => 'Jersey',
        'criollo'   => 'Criollo',
        'otra'      => 'Otra',
    ];

    const CATEGORIAS = [
        'vaca'       => 'Vaca',
        'toro'       => 'Toro',
        'novillo'    => 'Novillo',
        'vaquillona' => 'Vaquillona',
        'ternero'    => 'Ternero',
        'ternera'    => 'Ternera',
        'torito'     => 'Torito',
        'buey'       => 'Buey',
    ];

    const SEXOS = [
        'macho'  => 'Macho',
        'hembra' => 'Hembra',
    ];

    protected $fillable = [
        'id_empresa',
        'id_establecimiento',
        'caravana',
        'raza',
        'sexo',
        'categoria',
        'fecha_nacimiento',
        'fecha_ingreso',
        'peso_ingreso_kg',
        'peso_actual_kg',
        'color',
        'observaciones',
        'activo',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso'    => 'date',
        'peso_ingreso_kg'  => 'float',
        'peso_actual_kg'   => 'float',
        'activo'           => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['caravana', 'categoria', 'raza', 'sexo', 'peso_actual_kg', 'id_establecimiento', 'activo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRazaLabelAttribute(): string
    {
        return self::RAZAS[$this->raza] ?? ucfirst($this->raza ?? '');
    }

    public function getCategoriaLabelAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? ucfirst($this->categoria);
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function pesajes(): HasMany
    {
        return $this->hasMany(Pesaje::class, 'id_animal');
    }

    public function eventosSanidad(): HasMany
    {
        return $this->hasMany(EventoSanidad::class, 'id_animal');
    }

    public function eventosReproduccion(): HasMany
    {
        return $this->hasMany(EventoReproduccion::class, 'id_animal');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
