<?php

namespace App\Livewire\Compras;

use App\Models\Empresa;
use App\Services\MrbotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\Process\Process;

class SincronizarArca extends Component
{
    public string  $desde          = '';
    public string  $hasta          = '';
    public bool    $debug          = false;
    public ?string $idEmpresa      = null;   // empresa seleccionada

    public string $estado         = 'idle';  // idle | procesando | ok | error
    public array  $resultado      = [];
    public string $mensajeError   = '';
    public string $empresaSincronizada = '';

    public function mount(): void
    {
        $this->desde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hasta = Carbon::now()->format('Y-m-d');

        // Pre-seleccionar la primera empresa con ARCA activo
        $primera = Empresa::where('arca_activo', true)->first();
        $this->idEmpresa = $primera?->id;
    }

    public function sincronizar(MrbotService $mrbot): void
    {
        Gate::authorize('compras.crear');

        $this->validate([
            'desde'     => 'required|date',
            'hasta'     => 'required|date|after_or_equal:desde',
            'idEmpresa' => 'required|exists:empresas,id',
        ], [
            'desde.required'        => 'Ingresá la fecha de inicio.',
            'hasta.required'        => 'Ingresá la fecha de fin.',
            'hasta.after_or_equal'  => 'La fecha de fin debe ser igual o posterior al inicio.',
            'idEmpresa.required'    => 'Seleccioná una empresa.',
            'idEmpresa.exists'      => 'La empresa seleccionada no es válida.',
        ]);

        $empresa = Empresa::findOrFail($this->idEmpresa);

        if (!$empresa->arca_activo || !$empresa->arca_cuit_login || !$empresa->arca_clave_fiscal) {
            $this->estado       = 'error';
            $this->mensajeError = "La empresa \"{$empresa->razon_social}\" no tiene credenciales ARCA configuradas. Configurala en Sistema → Empresas.";
            return;
        }

        $this->estado             = 'procesando';
        $this->resultado          = [];
        $this->mensajeError       = '';
        $this->empresaSincronizada = $empresa->razon_social;

        $script = base_path('scripts/arca_descarga.js');

        $cmd = array_values(array_filter([
            'node',
            $script,
            '--cuit',  $empresa->arca_cuit_login,
            '--clave', $empresa->arca_clave_fiscal,
            '--desde', $this->desde,
            '--hasta', $this->hasta,
            // Si cuit_representado difiere del login, lo pasa explícitamente
            $empresa->arca_cuit_representado && $empresa->arca_cuit_representado !== $empresa->arca_cuit_login
                ? '--cuit-representado' : null,
            $empresa->arca_cuit_representado && $empresa->arca_cuit_representado !== $empresa->arca_cuit_login
                ? $empresa->arca_cuit_representado : null,
            $this->debug ? '--screenshots' : null,
        ]));

        $proceso = new Process($cmd, base_path(), null, null, 300);
        $proceso->run();

        if (!$proceso->isSuccessful()) {
            $this->estado       = 'error';
            $this->mensajeError = "El script terminó con error para {$empresa->razon_social}. Revisá los logs o ejecutá con --debug desde la consola.";
            return;
        }

        $stdout = trim($proceso->getOutput());

        if (empty($stdout)) {
            $this->estado       = 'error';
            $this->mensajeError = 'El script no devolvió datos. Verificá la consola con: php artisan arca:sincronizar --debug';
            return;
        }

        $data = json_decode($stdout, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->estado       = 'error';
            $this->mensajeError = 'El script devolvió JSON inválido.';
            return;
        }

        if (isset($data['__error__'])) {
            $this->estado       = 'error';
            $this->mensajeError = $data['__error__'];
            return;
        }

        $this->resultado = is_array($data)
            ? $mrbot->importarComprobantesJson($data, $empresa->id)
            : ['importadas' => 0, 'duplicadas' => 0, 'errores' => 0];

        $this->estado = 'ok';
    }

    public function render()
    {
        $empresasArca = Empresa::where('arca_activo', true)->orderBy('razon_social')->get();
        $todasEmpresas = Empresa::where('activa', true)->orderBy('razon_social')->get();

        return view('livewire.compras.sincronizar-arca', compact('empresasArca', 'todasEmpresas'));
    }
}
