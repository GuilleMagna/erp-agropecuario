<?php

namespace App\Livewire\Compras;

use App\Services\MrbotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\Process\Process;

class SincronizarArca extends Component
{
    public string $desde = '';
    public string $hasta  = '';
    public bool   $debug  = false;

    public string $estado = 'idle'; // idle | procesando | ok | error
    public array  $resultado = [];
    public string $mensajeError = '';
    public bool   $configurado = false;

    public function mount(): void
    {
        $this->configurado = !empty(config('mrbot.cuit_login')) && !empty(config('mrbot.clave_fiscal'));
        $this->desde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hasta  = Carbon::now()->format('Y-m-d');
    }

    public function sincronizar(MrbotService $mrbot): void
    {
        Gate::authorize('compras.crear');

        $this->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ], [
            'desde.required'       => 'Ingresá la fecha de inicio.',
            'hasta.required'       => 'Ingresá la fecha de fin.',
            'hasta.after_or_equal' => 'La fecha de fin debe ser igual o posterior al inicio.',
        ]);

        $this->estado       = 'procesando';
        $this->resultado    = [];
        $this->mensajeError = '';

        $cuit  = config('mrbot.cuit_login');
        $clave = config('mrbot.clave_fiscal');
        $script = base_path('scripts/arca_descarga.js');

        $cmd = array_filter([
            'node',
            $script,
            '--cuit',  $cuit,
            '--clave', $clave,
            '--desde', $this->desde,
            '--hasta', $this->hasta,
            $this->debug ? '--screenshots' : null,
        ]);

        $proceso = new Process(array_values($cmd), base_path(), null, null, 300);
        $proceso->run();

        if (!$proceso->isSuccessful()) {
            $this->estado       = 'error';
            $this->mensajeError = 'El script de descarga terminó con error. Revisá los logs o ejecutá con --debug desde la consola.';
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
            $this->mensajeError = 'El script devolvió JSON inválido. Ejecutá: php artisan arca:sincronizar --debug';
            return;
        }

        if (isset($data['__error__'])) {
            $this->estado       = 'error';
            $this->mensajeError = $data['__error__'];
            return;
        }

        // $data puede ser [] si no hay comprobantes en el período — es válido
        $idEmpresa = auth()->user()?->id_empresa;
        $this->resultado = is_array($data) ? $mrbot->importarComprobantesJson($data, $idEmpresa) : ['importadas' => 0, 'duplicadas' => 0, 'errores' => 0];
        $this->estado    = 'ok';
    }

    public function render()
    {
        return view('livewire.compras.sincronizar-arca');
    }

    private function pythonBin(): string
    {
        foreach (['py', 'python3', 'python'] as $bin) {
            exec("where {$bin} 2>NUL", $out, $code);
            if ($code === 0 && !empty($out)) {
                $ver = shell_exec("{$bin} --version 2>&1");
                if ($ver && !str_contains($ver, '0.0.0.0') && preg_match('/Python 3\./', $ver)) {
                    return $bin;
                }
            }
            $out = [];
        }
        return 'python';
    }
}
