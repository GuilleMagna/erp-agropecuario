<?php

namespace App\Models;

use App\Traits\PerteneceAEmpresa;
use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaccion extends Model
{
    use HasFactory, UsaUuid, PerteneceAEmpresa, LogsActivity;

    protected $table = 'transacciones';

    protected $fillable = [
        'id_cuenta', 'tipo', 'categoria', 'concepto', 'importe',
        'fecha', 'id_establecimiento', 'numero_comprobante', 'observaciones',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'fecha'   => 'date',
    ];

    const CATEGORIAS_INGRESO = [
        'venta_granos'          => 'Venta de granos',
        'venta_hacienda'        => 'Venta de hacienda',
        'venta_leche'           => 'Venta de leche',
        'alquiler_cobrado'      => 'Alquiler cobrado',
        'subsidios_aportes'     => 'Subsidios / Aportes',
        'transferencia_entrada' => 'Transferencia entrada',
        'otro_ingreso'          => 'Otro ingreso',
    ];

    const CATEGORIAS_EGRESO = [
        'semillas'                => 'Semillas',
        'agroquimicos'            => 'Agroquímicos',
        'fertilizantes'           => 'Fertilizantes',
        'combustible'             => 'Combustible y lubricantes',
        'sueldos_jornales'        => 'Sueldos y jornales',
        'alquiler_arrendamiento'  => 'Alquiler / Arrendamiento',
        'gastos_veterinarios'     => 'Gastos veterinarios',
        'maquinaria_reparaciones' => 'Maquinaria y reparaciones',
        'fletes_transporte'       => 'Fletes y transporte',
        'servicios_profesionales' => 'Honorarios / Servicios',
        'impuestos_tasas'         => 'Impuestos y tasas',
        'seguros'                 => 'Seguros',
        'gastos_comercializacion' => 'Gastos de comercialización',
        'transferencia_salida'    => 'Transferencia salida',
        'otro_egreso'             => 'Otro egreso',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo', 'categoria', 'concepto', 'importe', 'fecha', 'id_cuenta', 'numero_comprobante'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getCategoriaLabelAttribute(): string
    {
        return self::CATEGORIAS_INGRESO[$this->categoria]
            ?? self::CATEGORIAS_EGRESO[$this->categoria]
            ?? $this->categoria;
    }

    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class, 'id_cuenta');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'id_establecimiento');
    }
}
