<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cuenta extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'cuentas';

    protected $fillable = [
        'nombre', 'tipo', 'moneda', 'numero_cuenta', 'banco',
        'saldo_inicial', 'activa', 'observaciones',
    ];

    protected $casts = [
        'activa'        => 'boolean',
        'saldo_inicial' => 'decimal:2',
    ];

    const TIPOS = [
        'banco'    => 'Banco',
        'caja'     => 'Caja',
        'tarjeta'  => 'Tarjeta de débito',
        'credito'  => 'Tarjeta de crédito',
        'otro'     => 'Otro',
    ];

    const MONEDAS = [
        'ARS' => 'Pesos (ARS)',
        'USD' => 'Dólares (USD)',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'tipo', 'moneda', 'banco', 'numero_cuenta', 'saldo_inicial', 'activa'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function transacciones()
    {
        return $this->hasMany(Transaccion::class, 'id_cuenta');
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
