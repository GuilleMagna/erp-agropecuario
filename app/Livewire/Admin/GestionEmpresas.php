<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class GestionEmpresas extends Component
{
    // Modal
    public bool    $modalAbierto = false;
    public bool    $modoEdicion  = false;
    public ?string $editandoId   = null;

    // Datos fiscales
    public string $razon_social     = '';
    public string $cuit             = '';
    public string $condicion_fiscal = '';
    public string $domicilio_fiscal = '';
    public string $moneda_default   = 'ARS';

    // Credenciales ARCA
    public string $arca_cuit_login          = '';
    public string $arca_clave_fiscal        = '';   // vacío = no cambiar en edición
    public string $arca_cuit_representado   = '';
    public string $arca_nombre_representado = '';
    public bool   $arca_activo              = false;

    const CONDICIONES_FISCALES = [
        'responsable_inscripto' => 'Responsable Inscripto',
        'monotributista'        => 'Monotributista',
        'exento'                => 'Exento',
        'no_responsable'        => 'No responsable',
        'consumidor_final'      => 'Consumidor final',
    ];

    public function mount(): void
    {
        Gate::authorize('admin.empresas.gestionar');
    }

    protected function rules(): array
    {
        $unique = $this->modoEdicion
            ? 'unique:empresas,cuit,' . $this->editandoId
            : 'unique:empresas,cuit';

        return [
            'razon_social'             => 'required|string|max:200',
            'cuit'                     => ['required', 'string', 'max:20', 'regex:/^\d{2}-\d{8}-\d{1}$/', $unique],
            'condicion_fiscal'         => 'required|in:' . implode(',', array_keys(self::CONDICIONES_FISCALES)),
            'domicilio_fiscal'         => 'nullable|string|max:300',
            'moneda_default'           => 'required|in:ARS,USD',
            'arca_cuit_login'          => ['nullable', 'string', 'regex:/^\d{2}-\d{8}-\d{1}$/'],
            'arca_clave_fiscal'        => 'nullable|string|max:200',
            'arca_cuit_representado'   => ['nullable', 'string', 'regex:/^\d{2}-\d{8}-\d{1}$/'],
            'arca_nombre_representado' => 'nullable|string|max:200',
        ];
    }

    protected array $messages = [
        'razon_social.required'   => 'La razón social es obligatoria.',
        'cuit.required'           => 'El CUIT es obligatorio.',
        'cuit.regex'              => 'El CUIT debe tener el formato XX-XXXXXXXX-X.',
        'cuit.unique'             => 'Ya existe una empresa con ese CUIT.',
        'condicion_fiscal.required' => 'La condición fiscal es obligatoria.',
        'arca_cuit_login.regex'         => 'El CUIT de login debe tener el formato XX-XXXXXXXX-X.',
        'arca_cuit_representado.regex'  => 'El CUIT representado debe tener el formato XX-XXXXXXXX-X.',
    ];

    public function abrirModalCrear(): void
    {
        Gate::authorize('admin.empresas.gestionar');
        $this->limpiar();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('admin.empresas.gestionar');

        $empresa = Empresa::findOrFail($id);

        $this->editandoId   = $id;
        $this->modoEdicion  = true;
        $this->razon_social     = $empresa->razon_social;
        $this->cuit             = $empresa->cuit;
        $this->condicion_fiscal = $empresa->condicion_fiscal;
        $this->domicilio_fiscal = $empresa->domicilio_fiscal ?? '';
        $this->moneda_default   = $empresa->moneda_default;

        $this->arca_cuit_login          = $empresa->arca_cuit_login ?? '';
        $this->arca_clave_fiscal        = '';   // nunca se pre-carga
        $this->arca_cuit_representado   = $empresa->arca_cuit_representado ?? '';
        $this->arca_nombre_representado = $empresa->arca_nombre_representado ?? '';
        $this->arca_activo              = $empresa->arca_activo;

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('admin.empresas.gestionar');
        $this->validate();

        $datos = [
            'razon_social'     => $this->razon_social,
            'cuit'             => $this->cuit,
            'condicion_fiscal' => $this->condicion_fiscal,
            'domicilio_fiscal' => $this->domicilio_fiscal ?: null,
            'moneda_default'   => $this->moneda_default,
            'arca_cuit_login'          => $this->arca_cuit_login ?: null,
            'arca_cuit_representado'   => $this->arca_cuit_representado ?: null,
            'arca_nombre_representado' => $this->arca_nombre_representado ?: null,
            'arca_activo'              => $this->arca_activo,
        ];

        // La clave sólo se actualiza si se ingresó algo
        if (!empty(trim($this->arca_clave_fiscal))) {
            $datos['arca_clave_fiscal'] = $this->arca_clave_fiscal;
        }

        if ($this->modoEdicion) {
            $empresa = Empresa::findOrFail($this->editandoId);
            $empresa->update($datos);
            $msg = "Empresa \"{$empresa->razon_social}\" actualizada.";
        } else {
            $datos['activa'] = true;
            $empresa = Empresa::create($datos);
            $msg = "Empresa \"{$empresa->razon_social}\" creada.";
        }

        $this->cerrarModal();
        session()->flash('success', $msg);
    }

    public function toggleActiva(string $id): void
    {
        Gate::authorize('admin.empresas.gestionar');
        $empresa = Empresa::findOrFail($id);
        $empresa->update(['activa' => !$empresa->activa]);
        session()->flash('success', "Empresa \"{$empresa->razon_social}\" " . ($empresa->activa ? 'activada' : 'desactivada') . '.');
    }

    public function toggleArca(string $id): void
    {
        Gate::authorize('admin.empresas.gestionar');
        $empresa = Empresa::findOrFail($id);
        $empresa->update(['arca_activo' => !$empresa->arca_activo]);
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiar();
    }

    private function limpiar(): void
    {
        $this->reset([
            'editandoId', 'modoEdicion',
            'razon_social', 'cuit', 'condicion_fiscal', 'domicilio_fiscal',
            'arca_cuit_login', 'arca_clave_fiscal', 'arca_cuit_representado', 'arca_nombre_representado',
            'arca_activo',
        ]);
        $this->moneda_default = 'ARS';
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.gestion-empresas', [
            'empresas'            => Empresa::orderBy('razon_social')->get(),
            'condicionesFiscales' => self::CONDICIONES_FISCALES,
        ]);
    }
}
