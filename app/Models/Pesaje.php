<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pesaje extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa;

    protected $table = 'pesajes';

    protected $fillable = [
        'id_empresa',
        'id_establecimiento',
        'id_animal',
        'fecha',
        'categoria',
        'cantidad',
        'peso_kg',
        'observaciones',
    ];

    protected $casts = [
        'fecha'    => 'date',
        'cantidad' => 'integer',
        'peso_kg'  => 'float',
    ];

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'id_animal');
    }
}
