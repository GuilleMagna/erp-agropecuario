<?php

namespace App\Console\Commands;

use App\Mail\ReporteComprobantesArca;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Services\MrbotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;

class SincronizarComprobantesArca extends Command
{
    protected $signature = 'arca:sincronizar
                            {--empresa=  : UUID o "todas" (default: todas las empresas con ARCA activo)}
                            {--desde=    : Fecha inicio YYYY-MM-DD (default: primer día del mes actual)}
                            {--hasta=    : Fecha fin YYYY-MM-DD (default: hoy)}
                            {--debug     : Abre navegador visible con screenshots para depurar}
                            {--screenshots : Guarda screenshots en modo headless}';

    protected $description = 'Sincroniza Mis Comprobantes Recibidos desde ARCA para todas las empresas configuradas';

    public function handle(MrbotService $mrbot): int
    {
        $desde = $this->resolverFecha($this->option('desde'), Carbon::now()->startOfMonth()->format('Y-m-d'));
        $hasta = $this->resolverFecha($this->option('hasta'), Carbon::now()->format('Y-m-d'));

        $empresas = $this->resolverEmpresas();

        if ($empresas->isEmpty()) {
            $this->error('No hay empresas con ARCA activo. Configuralas en Sistema → Empresas.');

            return self::FAILURE;
        }

        $this->info("Período: {$desde} → {$hasta}");
        $this->info('Empresas a sincronizar: '.$empresas->count());

        $scriptPath = base_path('scripts/arca_descarga.js');

        if (! file_exists($scriptPath)) {
            $this->error("Script no encontrado: {$scriptPath}");

            return self::FAILURE;
        }

        $totalImportadas = 0;
        $totalDuplicadas = 0;
        $totalErrores = 0;
        $exitCode = self::SUCCESS;
        $comprasPorEmpresa = collect();

        foreach ($empresas as $empresa) {
            $this->newLine();
            $this->line("── <info>{$empresa->razon_social}</info> ({$empresa->cuit}) ──");

            if (! $empresa->arca_cuit_login || ! $empresa->arca_clave_fiscal) {
                $this->warn('  Sin credenciales ARCA. Saltando.');

                continue;
            }

            $cmd = array_values(array_filter([
                'node',
                $scriptPath,
                '--cuit',  $empresa->arca_cuit_login,
                '--clave', $empresa->arca_clave_fiscal,
                '--desde', $desde,
                '--hasta', $hasta,
                // Pasar cuit-representado sólo si difiere del login
                $empresa->arca_cuit_representado && $empresa->arca_cuit_representado !== $empresa->arca_cuit_login
                    ? '--cuit-representado' : null,
                $empresa->arca_cuit_representado && $empresa->arca_cuit_representado !== $empresa->arca_cuit_login
                    ? $empresa->arca_cuit_representado : null,
                $this->option('debug') ? '--debug' : null,
                $this->option('screenshots') ? '--screenshots' : null,
            ]));

            $this->line('  Consultando ARCA... (puede tardar hasta 90 seg)');

            $proceso = new Process($cmd, base_path(), null, null, 300);
            $proceso->run(function (string $type, string $buffer) {
                if ($type === Process::ERR) {
                    foreach (explode("\n", trim($buffer)) as $linea) {
                        if ($linea) {
                            $this->line("    {$linea}");
                        }
                    }
                }
            });

            if (! $proceso->isSuccessful()) {
                $this->error("  El script terminó con error para {$empresa->razon_social}.");
                $exitCode = self::FAILURE;

                continue;
            }

            $stdout = trim($proceso->getOutput());

            if (empty($stdout)) {
                $this->warn('  El script no devolvió datos.');

                continue;
            }

            $data = json_decode($stdout, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('  Respuesta no es JSON válido.');
                $exitCode = self::FAILURE;

                continue;
            }

            if (isset($data['__error__'])) {
                $this->error("  Error ARCA: {$data['__error__']}");
                $exitCode = self::FAILURE;

                continue;
            }

            if (empty($data)) {
                $this->info('  Sin comprobantes en el período.');

                continue;
            }

            $resultado = $mrbot->importarComprobantesJson($data, $empresa->id);

            $this->info("  Importadas: {$resultado['importadas']}  Duplicadas: {$resultado['duplicadas']}  Errores: {$resultado['errores']}");

            $totalImportadas += $resultado['importadas'];
            $totalDuplicadas += $resultado['duplicadas'];
            $totalErrores += $resultado['errores'];

            if ($resultado['compras']->isNotEmpty()) {
                $comprasPorEmpresa->put($empresa->razon_social, $resultado['compras']);
            }
        }

        $this->newLine();
        $this->line('─────────────────────────────────────');
        $this->info("Total importadas : {$totalImportadas}");
        $this->line("Total duplicadas : {$totalDuplicadas}");

        if ($totalErrores > 0) {
            $this->warn("Total con errores: {$totalErrores}");
        }

        if ($comprasPorEmpresa->isNotEmpty()) {
            $this->enviarReporte($comprasPorEmpresa, $desde, $hasta);
        }

        return $exitCode;
    }

    private function enviarReporte($comprasPorEmpresa, string $desde, string $hasta): void
    {
        $destinatarios = Usuario::role('administrador_sistema')
            ->where('recibir_notificaciones', true)
            ->where('activo', true)
            ->pluck('email');

        if ($destinatarios->isEmpty()) {
            $this->warn('Ningún administrador tiene activadas las notificaciones por email: no se envía el informe.');

            return;
        }

        try {
            Mail::to($destinatarios->all())->send(new ReporteComprobantesArca($comprasPorEmpresa, $desde, $hasta));
            $this->info('Informe enviado a: '.$destinatarios->implode(', '));
        } catch (\Exception $e) {
            // Un error de envío de mail no debe hacer fallar la sincronización:
            // los comprobantes ya se importaron correctamente a esta altura.
            Log::error('SincronizarComprobantesArca: error al enviar el informe por email', [
                'error' => $e->getMessage(),
            ]);
            $this->error("No se pudo enviar el informe por email: {$e->getMessage()}");
        }
    }

    private function resolverEmpresas()
    {
        $opcion = $this->option('empresa');

        if ($opcion && $opcion !== 'todas') {
            $empresa = Empresa::where('id', $opcion)
                ->orWhere('cuit', $opcion)
                ->first();

            if (! $empresa) {
                $this->error("Empresa no encontrada: {$opcion}");

                return collect();
            }

            return collect([$empresa]);
        }

        return Empresa::where('arca_activo', true)
            ->whereNotNull('arca_cuit_login')
            ->whereNotNull('arca_clave_fiscal')
            ->orderBy('razon_social')
            ->get();
    }

    private function resolverFecha(?string $input, string $default): string
    {
        if (! $input) {
            return $default;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $input);
                if ($d) {
                    return $d->format('Y-m-d');
                }
            } catch (\Exception) {
            }
        }

        return $default;
    }
}
