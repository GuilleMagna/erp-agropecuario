<?php

namespace App\Livewire\Sistema;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ConfiguracionEmpresa extends Component
{
    public string $razon_social     = '';
    public string $cuit             = '';
    public string $condicion_fiscal = '';
    public string $domicilio_fiscal = '';
    public string $moneda_default   = 'ARS';

    const CONDICIONES_FISCALES = [
        'responsable_inscripto' => 'Responsable Inscripto',
        'monotributista'        => 'Monotributista',
        'exento'                => 'Exento',
        'no_responsable'        => 'No responsable',
        'consumidor_final'      => 'Consumidor final',
    ];

    const MONEDAS = [
        'ARS' => 'Pesos argentinos (ARS)',
        'USD' => 'Dólares estadounidenses (USD)',
    ];

    public function mount(): void
    {
        Gate::authorize('admin.roles.gestionar');

        $empresa = auth()->user()->empresa;
        $this->razon_social     = $empresa->razon_social ?? '';
        $this->cuit             = $empresa->cuit ?? '';
        $this->condicion_fiscal = $empresa->condicion_fiscal ?? '';
        $this->domicilio_fiscal = $empresa->domicilio_fiscal ?? '';
        $this->moneda_default   = $empresa->moneda_default ?? 'ARS';
    }

    public function guardar(): void
    {
        Gate::authorize('admin.roles.gestionar');

        $this->validate([
            'razon_social'     => 'required|string|max:200',
            'cuit'             => ['required', 'string', 'max:20', 'regex:/^\d{2}-\d{8}-\d{1}$/'],
            'condicion_fiscal' => 'required|string|in:' . implode(',', array_keys(self::CONDICIONES_FISCALES)),
            'domicilio_fiscal' => 'nullable|string|max:300',
            'moneda_default'   => 'required|in:ARS,USD',
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'cuit.required'         => 'El CUIT es obligatorio.',
            'cuit.regex'            => 'El CUIT debe tener el formato XX-XXXXXXXX-X.',
            'condicion_fiscal.required' => 'La condición fiscal es obligatoria.',
        ]);

        auth()->user()->empresa->update([
            'razon_social'     => $this->razon_social,
            'cuit'             => $this->cuit,
            'condicion_fiscal' => $this->condicion_fiscal,
            'domicilio_fiscal' => $this->domicilio_fiscal ?: null,
            'moneda_default'   => $this->moneda_default,
        ]);

        session()->flash('success', 'Configuración de empresa guardada correctamente.');
    }

    public function render()
    {
        return view('livewire.sistema.configuracion-empresa', [
            'condicionesFiscales' => self::CONDICIONES_FISCALES,
            'monedas'             => self::MONEDAS,
        ]);
    }
}
